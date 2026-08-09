<?php

namespace App\Console\Commands;

use App\Services\Phase9BControlledCutoverService;
use App\Services\Phase9BStage4Guard;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class ValidatePhase9BControlledCutover extends Command
{
    protected $signature = 'phase9b:validate-controlled-cutover';

    protected $description = 'Run one guarded marked Supabase new-write validation while maintenance remains active';

    public function handle(
        Phase9BStage4Guard $guard,
        Phase9BControlledCutoverService $service,
    ): int {
        try {
            $baseline = $guard->assertReady();
            $this->line('Baseline uploaded photographs: '.$baseline['inventory']['uploaded_photos']);
            $result = $service->run();

            $expectedLocalFiles = $baseline['inventory']['local_files']
                + ($result['new_test_row_created'] ? 1 : 0);
            if ($result['local_orphans'] !== $baseline['inventory']['local_orphans']) {
                throw new RuntimeException('Controlled validation changed the pre-existing local orphan count.');
            }
            if ($result['local_files'] !== $expectedLocalFiles) {
                throw new RuntimeException('Controlled validation did not preserve the expected local rollback evidence.');
            }

            foreach ($result as $name => $value) {
                $this->line($name.': '.(is_bool($value) ? ($value ? 'yes' : 'no') : $value));
            }
            $this->info('Phase 9B Stage 4 controlled cutover validation passed.');
            $this->warn('Maintenance mode remains active. Normal writes are still blocked.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Phase 9B Stage 4 stopped safely: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
