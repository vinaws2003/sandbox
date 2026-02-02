<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Monitor scheduled commands
Schedule::command('monitor:collect')->everyMinute();
Schedule::command('monitor:check-alerts')->everyMinute();
Schedule::command('monitor:cleanup')->daily();
