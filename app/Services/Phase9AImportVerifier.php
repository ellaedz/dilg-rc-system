<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

class Phase9AImportVerifier
{
    /**
     * @return array{table_counts: array<string, int>, table_digests: array<string, string>, index_count: int, foreign_key_count: int}
     */
    public function verify(
        string $sourcePath,
        string $manifestPath,
        string $expectedSourceHash,
        string $connectionName,
        string $schema,
    ): array {
        $source = $this->openVerifiedSnapshot($sourcePath, $manifestPath, $expectedSourceHash);
        $target = DB::connection($connectionName);
        $counts = [];
        $digests = [];

        foreach ((array) config('phase9a.import_tables') as $table) {
            $columns = $this->targetColumns($target, $schema, $table);
            $sourceCount = (int) $source
                ->query('SELECT COUNT(*) FROM '.$this->quoteIdentifier($table))
                ->fetchColumn();
            $targetCount = (int) $target->table($table)->count();

            if ($sourceCount !== $targetCount) {
                throw new RuntimeException("Parity count mismatch for {$table}.");
            }

            $sourceDigest = $this->sourceDigest($source, $table, $columns);
            $targetDigest = $this->targetDigest($target, $table, $columns);

            if (! hash_equals($sourceDigest, $targetDigest)) {
                $mismatchedColumns = $this->mismatchedColumns(
                    source: $source,
                    target: $target,
                    table: $table,
                    columns: $columns,
                );
                $columnSummary = $mismatchedColumns === []
                    ? 'row associations differ'
                    : implode(', ', $mismatchedColumns);

                throw new RuntimeException(
                    "Canonical digest mismatch for {$table}; differing columns: {$columnSummary}."
                );
            }

            $counts[$table] = $sourceCount;
            $digests[$table] = $sourceDigest;
        }

        $this->assertPostgresTypeContract($target, $schema);
        $this->assertSecurityAndRelationships($target);

        $indexCount = (int) $target->scalar(
            'SELECT COUNT(*) FROM pg_indexes WHERE schemaname = ?',
            [$schema],
        );
        $foreignKeyCount = (int) $target->scalar(
            "SELECT COUNT(*)
             FROM pg_constraint constraints
             JOIN pg_namespace schemas ON schemas.oid = constraints.connamespace
             WHERE schemas.nspname = ? AND constraints.contype = 'f'",
            [$schema],
        );

        if ($indexCount === 0 || $foreignKeyCount < 2) {
            throw new RuntimeException('PostgreSQL indexes or foreign keys are incomplete.');
        }

        return [
            'table_counts' => $counts,
            'table_digests' => $digests,
            'index_count' => $indexCount,
            'foreign_key_count' => $foreignKeyCount,
        ];
    }

    private function openVerifiedSnapshot(
        string $sourcePath,
        string $manifestPath,
        string $expectedSourceHash,
    ): PDO {
        $sourcePath = realpath($sourcePath) ?: '';
        $manifestPath = realpath($manifestPath) ?: '';

        if ($sourcePath === '' || $manifestPath === '') {
            throw new RuntimeException('Snapshot or manifest path is invalid.');
        }

        if ($sourcePath === (realpath(database_path('database.sqlite')) ?: '')) {
            throw new RuntimeException('Verification must use the immutable SQLite snapshot.');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $actualHash = hash_file('sha256', $sourcePath);
        $manifestHash = strtolower((string) data_get($manifest, 'backup.sha256'));
        $manifestSource = realpath((string) data_get($manifest, 'backup.path')) ?: '';

        if (! is_string($actualHash)
            || ! preg_match('/\A[a-f0-9]{64}\z/iD', $expectedSourceHash)
            || ! hash_equals(strtolower($expectedSourceHash), strtolower($actualHash))
            || ! hash_equals($manifestHash, strtolower($actualHash))
            || $manifestSource !== $sourcePath) {
            throw new RuntimeException('Snapshot hash or manifest verification failed.');
        }

        $source = new PDO('sqlite:'.$sourcePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $source->exec('PRAGMA query_only = ON');

        if ($source->query('PRAGMA integrity_check')->fetchColumn() !== 'ok'
            || $source->query('PRAGMA foreign_key_check')->fetchAll() !== []) {
            throw new RuntimeException('The immutable SQLite snapshot is not healthy.');
        }

        return $source;
    }

    /**
     * @return array<string, array{data_type: string, numeric_scale: ?int}>
     */
    private function targetColumns(
        ConnectionInterface $target,
        string $schema,
        string $table,
    ): array {
        $rows = $target->select(
            'SELECT column_name, data_type, numeric_scale
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?
             ORDER BY ordinal_position',
            [$schema, $table],
        );
        $columns = [];

        foreach ($rows as $row) {
            $columns[$row->column_name] = [
                'data_type' => $row->data_type,
                'numeric_scale' => $row->numeric_scale === null
                    ? null
                    : (int) $row->numeric_scale,
            ];
        }

        if ($columns === []) {
            throw new RuntimeException("Target table {$table} is missing.");
        }

        return $columns;
    }

    /**
     * @param  array<string, array{data_type: string, numeric_scale: ?int}>  $columns
     */
    private function sourceDigest(PDO $source, string $table, array $columns): string
    {
        $rows = $source->query('SELECT * FROM '.$this->quoteIdentifier($table))->fetchAll();

        return $this->canonicalDigest($rows, $columns);
    }

    /**
     * @param  array<string, array{data_type: string, numeric_scale: ?int}>  $columns
     */
    private function targetDigest(
        ConnectionInterface $target,
        string $table,
        array $columns,
    ): string {
        $rows = array_map(
            static fn (object $row): array => (array) $row,
            $target->table($table)->get()->all(),
        );

        return $this->canonicalDigest($rows, $columns);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, array{data_type: string, numeric_scale: ?int}>  $columns
     */
    private function canonicalDigest(array $rows, array $columns): string
    {
        $rowHashes = [];

        foreach ($rows as $row) {
            ksort($row);

            foreach ($row as $column => $value) {
                $row[$column] = $this->canonicalValue($value, $columns[$column] ?? null);
            }

            $encoded = json_encode(
                $row,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
            );

            if (! is_string($encoded)) {
                throw new RuntimeException('A row could not be encoded for canonical verification.');
            }

            $rowHashes[] = hash('sha256', $encoded);
        }

        sort($rowHashes, SORT_STRING);

        return hash('sha256', implode("\n", $rowHashes));
    }

    /**
     * Report column names only. Imported values can include private citizen or
     * security data and must never be printed by the diagnostic.
     *
     * @param  array<string, array{data_type: string, numeric_scale: ?int}>  $columns
     * @return list<string>
     */
    private function mismatchedColumns(
        PDO $source,
        ConnectionInterface $target,
        string $table,
        array $columns,
    ): array {
        $sourceRows = $source
            ->query('SELECT * FROM '.$this->quoteIdentifier($table))
            ->fetchAll();
        $targetRows = array_map(
            static fn (object $row): array => (array) $row,
            $target->table($table)->get()->all(),
        );
        $mismatched = [];

        foreach ($columns as $column => $definition) {
            $sourceValues = array_map(
                fn (array $row): mixed => $this->canonicalValue(
                    $row[$column] ?? null,
                    $definition,
                ),
                $sourceRows,
            );
            $targetValues = array_map(
                fn (array $row): mixed => $this->canonicalValue(
                    $row[$column] ?? null,
                    $definition,
                ),
                $targetRows,
            );
            $sourceEncoded = array_map([$this, 'encodeCanonicalValue'], $sourceValues);
            $targetEncoded = array_map([$this, 'encodeCanonicalValue'], $targetValues);
            sort($sourceEncoded, SORT_STRING);
            sort($targetEncoded, SORT_STRING);

            if ($sourceEncoded !== $targetEncoded) {
                $mismatched[] = $column;
            }
        }

        return $mismatched;
    }

    private function encodeCanonicalValue(mixed $value): string
    {
        $encoded = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        );

        if (! is_string($encoded)) {
            throw new RuntimeException('A value could not be encoded for parity diagnostics.');
        }

        return $encoded;
    }

    /**
     * @param  array{data_type: string, numeric_scale: ?int}|null  $definition
     */
    private function canonicalValue(mixed $value, ?array $definition): mixed
    {
        if ($value === null || $definition === null) {
            return $value;
        }

        $type = $definition['data_type'];

        if ($type === 'boolean') {
            return in_array($value, [true, 1, '1', 't', 'true'], true);
        }

        if (in_array($type, ['smallint', 'integer', 'bigint'], true)) {
            return (string) $value;
        }

        if (in_array($type, ['numeric', 'decimal', 'real', 'double precision'], true)) {
            return $this->canonicalNumber((string) $value);
        }

        if (in_array($type, ['json', 'jsonb'], true)) {
            $decoded = json_decode((string) $value, true);

            return $this->sortJson($decoded);
        }

        if ($type === 'date') {
            $date = substr((string) $value, 0, 10);

            if (! preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $date)) {
                throw new RuntimeException('A date value could not be normalized for verification.');
            }

            return $date;
        }

        if (str_contains($type, 'timestamp')) {
            return preg_replace('/\.0+\z/', '', str_replace('T', ' ', (string) $value));
        }

        return $value;
    }

    private function canonicalNumber(string $value): string
    {
        $value = trim($value);
        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = rtrim($fraction, '0');
        $normalized = $fraction === '' ? $whole : $whole.'.'.$fraction;

        return $negative && $normalized !== '0' ? '-'.$normalized : $normalized;
    }

    private function sortJson(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->sortJson($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->sortJson($item);
        }

        return $value;
    }

    private function assertPostgresTypeContract(ConnectionInterface $target, string $schema): void
    {
        $expectedTypes = [
            'municipality_validated' => 'boolean',
            'needs_manual_barangay_review' => 'boolean',
            'needs_manual_review' => 'boolean',
            'ai_needs_manual_review' => 'boolean',
            'is_duplicate' => 'boolean',
            'is_test_data' => 'boolean',
            'ai_raw_response' => 'json',
            'ai_image_detections' => 'json',
            'ai_gis_result' => 'json',
            'ai_model_metadata' => 'json',
            'ai_timing' => 'json',
            'ai_manual_review_reasons' => 'json',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'gps_accuracy' => 'numeric',
            'date_submitted' => 'date',
            'date_updated' => 'date',
        ];
        $expectedNumerics = [
            'latitude' => [10, 8],
            'longitude' => [11, 8],
            'gps_accuracy' => [20, 12],
            'confidence_score' => [7, 6],
            'response_time_hours' => [20, 12],
            'text_confidence' => [5, 4],
            'final_ai_confidence' => [5, 4],
            'ai_possible_violation_confidence' => [5, 4],
            'ai_image_confidence' => [7, 6],
        ];

        $rows = $target->select(
            'SELECT column_name, data_type, numeric_precision, numeric_scale
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?',
            [$schema, 'violation_reports'],
        );
        $actual = [];

        foreach ($rows as $row) {
            $actual[$row->column_name] = [
                'data_type' => $row->data_type,
                'numeric_precision' => $row->numeric_precision === null
                    ? null
                    : (int) $row->numeric_precision,
                'numeric_scale' => $row->numeric_scale === null
                    ? null
                    : (int) $row->numeric_scale,
            ];
        }

        foreach ($expectedTypes as $column => $type) {
            if (($actual[$column]['data_type'] ?? null) !== $type) {
                throw new RuntimeException("PostgreSQL type mismatch for violation_reports.{$column}.");
            }
        }

        foreach ($expectedNumerics as $column => [$precision, $scale]) {
            if (($actual[$column]['numeric_precision'] ?? null) !== $precision
                || ($actual[$column]['numeric_scale'] ?? null) !== $scale) {
                throw new RuntimeException(
                    "PostgreSQL numeric precision mismatch for violation_reports.{$column}."
                );
            }
        }
    }

    private function assertSecurityAndRelationships(ConnectionInterface $target): void
    {
        $reportCount = (int) $target->table('violation_reports')->count();
        $uniqueReports = (int) $target->table('violation_reports')
            ->distinct()
            ->count('report_number');

        if ($reportCount !== $uniqueReports) {
            throw new RuntimeException('Report Number parity or uniqueness failed.');
        }

        foreach (['tracking_token_hash', 'idempotency_key_hash', 'token_derivation_nonce'] as $column) {
            $count = (int) $target->table('violation_reports')->whereNotNull($column)->count();
            $unique = (int) $target->table('violation_reports')
                ->whereNotNull($column)
                ->distinct()
                ->count($column);

            if ($count !== $unique) {
                throw new RuntimeException("Security hash uniqueness failed for {$column}.");
            }
        }

        $orphanTimelines = (int) $target->table('report_timelines as timelines')
            ->leftJoin('violation_reports as reports', 'reports.id', '=', 'timelines.report_id')
            ->whereNull('reports.id')
            ->count();

        if ($orphanTimelines !== 0) {
            throw new RuntimeException('Timeline relationship parity failed.');
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (! preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $identifier)) {
            throw new RuntimeException('Unsafe SQLite identifier.');
        }

        return '"'.$identifier.'"';
    }
}
