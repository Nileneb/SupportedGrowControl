<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process scheduled calendar events (Shelly + Device commands) every minute
Schedule::command('events:process')->everyMinute();

// Timeout pending and executing commands to keep UI accurate
Schedule::command('commands:timeout --minutes=5')->everyFiveMinutes();
Schedule::command('commands:timeout-executing --minutes=10')->everyTenMinutes();
