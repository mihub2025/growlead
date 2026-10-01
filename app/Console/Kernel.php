<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('crm:monitor-sla')->everyFiveMinutes();
        $schedule->command('crm:overdue-tasks')->everyFifteenMinutes();
        $schedule->command('crm:sync-meta')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('queue:prune-failed --hours=168')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
