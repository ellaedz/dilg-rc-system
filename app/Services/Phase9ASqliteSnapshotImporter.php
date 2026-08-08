<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

class Phase9ASqliteSnapshotImporter
{
    /**
     * @return array{source_sha256: string, table_counts: array<string, int>, imported_rows: int}
     */
    public function import(
        string $sourcePath,
        string $manifestPath,
        string $expectedSourceHash,
        string $connectionName,
        string $schema,
    ): array {
        [$source, $manifest, $sourceHash] = $this->verifySnapshot(
            $sourcePath,
            $manifestPath,
            $expectedSourceHash,
        );

        $target = DB::connection($connectionName);
        $tables = (array) config('phase9a.import_tables');
        $sourceCounts = [];
        $importedRows = 0;

        foreach ($tables as $table) {
            $sourceCounts[$table] = $this->sourceCount($source, $table);
        }

        $target->transaction(function () use (
            $source,
            $target,
            $tables,
            $schema,
            $sourceCounts,
            &$importedRows,
        ): void {
            foreach ($tables as $table) {
                $columns = $this->assertCompatibleTable($source, $target, $schema, $table);
                $targetCount = (int) $target->table($table)->count();

                if ($targetCount !== 0) {
                    throw new RuntimeException("Target table {$table} is not empty.");
                }

                $statement = $source->query('SELECT * FROM '.$this->quoteSqliteIdentifier($table));
                $batch = [];

                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $batch[] = $this->normalizeRowForPostgres($row, $columns);

                    if (count($batch) === 200) {
                        $target->table($table)->insert($batch);
                        $importedRows += count($batch);
                        $batch = [];
                    }
                }

                if ($batch !== []) {
                    $target->table($table)->insert($batch);
                    $importedRows += count($batch);
                }

                if ((int) $target->table($table)->count() !== $sourceCounts[$table]) {
                    throw new RuntimeException("Row-count mismatch after importing {$table}.");
                }

                if (array_key_exists('id', $columns)) {
                    $this->synchronizeIdSequence($target, $schema, $table);
                }
            }

            $this->verifyRelationshipsAndSecurity($target);
        }, 1);

        clearstatcache(true, $sourcePath);
        $hashAfterImport = hash_file('sha256', $sourcePath);

        if (! is_string($hashAfterImport) || ! hash_equals($sourceHash, $hashAfterImport)) {
            throw new RuntimeException('The immutable SQLite snapshot changed during import.');
        }

        return [
            'source_sha256' => $sourceHash,
            'table_counts' => $sourceCounts,
            'imported_rows' => $importedRows,
        ];
    }

    /**
     * @return array{PDO, array<string, mixed>, string}
     */
    private function verifySnapshot(
        string $sourcePath,
        string $manifestPath,
        string $expectedSourceHash,
    ): array {
        $sourcePath = realpath($sourcePath) ?: '';
        $manifestPath = realpath($manifestPath) ?: '';
        $livePath = realpath(database_path('database.sqlite')) ?: '';

        if ($sourcePath === '' || ! is_file($sourcePath)) {
            throw new RuntimeException('The SQLite snapshot does not exist.');
        }

        if ($livePath !== '' && $sourcePath === $livePath) {
            throw new RuntimeException('Importing from the live SQLite database is forbidden.');
        }

        if ($manifestPath === '' || ! is_file($manifestPath)) {
            throw new RuntimeException('The SQLite snapshot manifest does not exist.');
        }

        if (! preg_match('/\A[a-f0-9]{64}\z/iD', $expectedSourceHash)) {
            throw new RuntimeException('A complete expected source SHA-256 is required.');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest)) {
            throw new RuntimeException('The SQLite snapshot manifest is invalid JSON.');
        }

        $manifestPathValue = realpath((string) data_get($manifest, 'backup.path')) ?: '';
        $manifestHash = strtolower((string) data_get($manifest, 'backup.sha256'));
        $sourceHash = hash_file('sha256', $sourcePath);

        if ($manifestPathValue !== $sourcePath) {
            throw new RuntimeException('The manifest does not identify the selected SQLite snapshot.');
        }

        if (! is_string($sourceHash)
            || ! hash_equals(strtolower($expectedSourceHash), strtolower($sourceHash))
            || ! hash_equals($manifestHash, strtolower($sourceHash))) {
            throw new RuntimeException('The SQLite snapshot SHA-256 does not match its approval manifest.');
        }

        $source = new PDO('sqlite:'.$sourcePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $source->exec('PRAGMA query_only = ON');

        if ($source->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException('The SQLite snapshot failed integrity_check.');
        }

        if ($source->query('PRAGMA foreign_key_check')->fetchAll() !== []) {
            throw new RuntimeException('The SQLite snapshot contains foreign-key violations.');
        }

        return [$source, $manifest, strtolower($sourceHash)];
    }

    private function sourceCount(PDO $source, string $table): int
    {
        return (int) $source
            ->query('SELECT COUNT(*) FROM '.$this->quoteSqliteIdentifier($table))
            ->fetchColumn();
    }

    /**
     * @return array<string, array{data_type: string, numeric_scale: ?int}>
     */
    private function assertCompatibleTable(
        PDO $source,
        ConnectionInterface $target,
        string $schema,
        string $table,
    ): array {
        $sourceColumns = array_column(
            $source->query('PRAGMA table_info('.$this->quoteSqliteIdentifier($table).')')->fetchAll(),
            'name',
        );
        $targetRows = $target->select(
            'SELECT column_name, data_type, numeric_scale
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?
             ORDER BY ordinal_position',
            [$schema, $table],
        );
        $targetColumns = [];

        foreach ($targetRows as $column) {
            $targetColumns[$column->column_name] = [
                'data_type' => $column->data_type,
                'numeric_scale' => $column->numeric_scale === null
                    ? null
                    : (int) $column->numeric_scale,
            ];
        }

        $sourceSorted = $sourceColumns;
        $targetSorted = array_keys($targetColumns);
        sort($sourceSorted);
        sort($targetSorted);

        if ($sourceSorted !== $targetSorted) {
            throw new RuntimeException("Column mismatch for table {$table}.");
        }

        return $targetColumns;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, array{data_type: string, numeric_scale: ?int}>  $columns
     * @return array<string, mixed>
     */
    private function normalizeRowForPostgres(array $row, array $columns): array
    {
        foreach ($row as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (($columns[$name]['data_type'] ?? null) === 'boolean') {
                $row[$name] = (bool) $value;
            }
        }

        return $row;
    }

    private function synchronizeIdSequence(
        ConnectionInterface $target,
        string $schema,
        string $table,
    ): void {
        $sequence = $target->scalar(
            'SELECT pg_get_serial_sequence(?, ?)',
            [$schema.'.'.$table, 'id'],
        );

        if (! is_string($sequence) || $sequence === '') {
            return;
        }

        $maximum = $target->table($table)->max('id');

        if ($maximum === null) {
            $target->select('SELECT setval(?::regclass, 1, false)', [$sequence]);

            return;
        }

        $target->select('SELECT setval(?::regclass, ?, true)', [$sequence, (int) $maximum]);
    }

    private function verifyRelationshipsAndSecurity(ConnectionInterface $target): void
    {
        $orphanTimelines = (int) $target->table('report_timelines as timelines')
            ->leftJoin('violation_reports as reports', 'reports.id', '=', 'timelines.report_id')
            ->whereNull('reports.id')
            ->count();
        $orphanVerifiers = (int) $target->table('violation_reports as reports')
            ->leftJoin('users', 'users.id', '=', 'reports.verified_by')
            ->whereNotNull('reports.verified_by')
            ->whereNull('users.id')
            ->count();
        $reportCount = (int) $target->table('violation_reports')->count();
        $uniqueReportNumbers = (int) $target->table('violation_reports')
            ->distinct()
            ->count('report_number');

        if ($orphanTimelines !== 0 || $orphanVerifiers !== 0) {
            throw new RuntimeException('Imported CIVICLEAR relationships contain orphan records.');
        }

        if ($reportCount !== $uniqueReportNumbers) {
            throw new RuntimeException('Imported Report Numbers are null or not unique.');
        }

        foreach ([
            'tracking_token_hash',
            'idempotency_key_hash',
            'token_derivation_nonce',
        ] as $column) {
            $nonNull = (int) $target->table('violation_reports')->whereNotNull($column)->count();
            $unique = (int) $target->table('violation_reports')
                ->whereNotNull($column)
                ->distinct()
                ->count($column);

            if ($nonNull !== $unique) {
                throw new RuntimeException("Imported security field {$column} is not unique.");
            }
        }
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        if (! preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $identifier)) {
            throw new RuntimeException('Unsafe SQLite identifier.');
        }

        return '"'.$identifier.'"';
    }
}
