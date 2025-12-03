<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Run scheduled calendar events every minute
        $schedule->command('events:run-scheduled')->everyMinute();

        // Timeout pending and executing commands to keep UI accurate
        $schedule->command('commands:timeout --minutes=5')->everyFiveMinutes();
        $schedule->command('commands:timeout-executing --minutes=10')->everyTenMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
