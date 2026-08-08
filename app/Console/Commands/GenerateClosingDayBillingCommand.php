<?php

namespace App\Console\Commands;

use App\Services\BillingGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateClosingDayBillingCommand extends Command
{
    protected $signature = 'billing:generate-closing-day
                            {--date= : As-of date (Y-m-d). Defaults to today.}
                            {--no-overwrite : Skip institutions that already have a snapshot for that date}';

    protected $description = 'Auto-generate billing snapshots for hospitals whose 締め日 is the given date';

    public function handle(BillingGenerationService $billing): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $overwrite = ! $this->option('no-overwrite');

        $this->info("Generating closing-day billing for {$date->toDateString()}…");

        $result = $billing->generateDueClosingDay($date, $overwrite);

        $this->info("Created: {$result['generated']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}");

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
