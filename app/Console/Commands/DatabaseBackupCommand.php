<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup {--scheduled : Run only when auto-schedule settings say it is due}';

    protected $description = 'Create a database backup and prune old backups by retain_days';

    public function handle(DatabaseBackupService $service): int
    {
        try {
            if ($this->option('scheduled')) {
                $backup = $service->runScheduledIfDue();

                if (! $backup) {
                    $this->info('Scheduled backup skipped (not due or disabled).');

                    return self::SUCCESS;
                }

                $this->info("Scheduled backup created: {$backup->filename}");

                return self::SUCCESS;
            }

            $backup = $service->createBackup(null, 'manual');
            $this->info("Backup created: {$backup->filename} ({$backup->humanSize()})");

            $pruned = $service->pruneOldBackups();
            if ($pruned > 0) {
                $this->info("Pruned {$pruned} old backup(s).");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
