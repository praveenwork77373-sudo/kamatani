<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Database backup schedule
|--------------------------------------------------------------------------
|
| Runs every minute; the command checks DatabaseBackupSetting and only
| creates a backup when enabled and the configured time/frequency matches.
|
*/
Schedule::command('db:backup --scheduled')->everyMinute()->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Monthly billing (病院ごとの締め日)
|--------------------------------------------------------------------------
|
| Runs daily; generates/updates snapshots for institutions whose
| billing_closing_day matches today's day-of-month.
|
*/
Schedule::command('billing:generate-closing-day')->dailyAt('01:00')->withoutOverlapping();
