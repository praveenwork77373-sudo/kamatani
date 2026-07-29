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
