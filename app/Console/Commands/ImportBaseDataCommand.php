<?php

namespace App\Console\Commands;

use App\Services\BaseDataImportService;
use Illuminate\Console\Command;

class ImportBaseDataCommand extends Command
{
    protected $signature = 'masters:import-base-data
        {file? : Path to ベースデータ xlsx (default: clientdocs/260805.ベースデータ.xlsx)}
        {--dry-run : Parse and map without committing}';

    protected $description = 'Import client base-data Excel (makers, products, hospitals, plans) with best-fit mapping';

    public function handle(BaseDataImportService $service): int
    {
        $default = base_path('clientdocs/260805.ベースデータ.xlsx');
        // NFD/NFC filename fallback for macOS
        if (! is_file($default)) {
            $matches = glob(base_path('clientdocs/260805*.xlsx')) ?: [];
            $default = $matches[0] ?? $default;
        }

        $path = $this->argument('file') ?: $default;
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[dry-run] ' : '')."Importing base data from: {$path}");

        try {
            $result = $service->import($path, $dryRun);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $stats = $result['stats'];
        $this->table(
            ['Metric', 'Count'],
            [
                ['Makers upserted', $stats['makers']],
                ['Product rows upserted', $stats['products']],
                ['Stub products created', $stats['stub_products']],
                ['Institutions upserted', $stats['institutions']],
                ['Contract plans upserted', $stats['plans']],
                ['Hospital–plan links', $stats['plan_links']],
                ['Warnings', $stats['warnings']],
            ]
        );

        foreach (array_slice($result['warnings'], 0, 30) as $warning) {
            $this->warn($warning);
        }
        if (count($result['warnings']) > 30) {
            $this->warn('... and '.(count($result['warnings']) - 30).' more warnings');
        }

        if ($dryRun) {
            $this->info('Dry run complete — no data was committed.');
        } else {
            $this->info('Import complete.');
        }

        return self::SUCCESS;
    }
}
