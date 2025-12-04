<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Schedules are now defined in routes/console.php for Laravel 12
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
