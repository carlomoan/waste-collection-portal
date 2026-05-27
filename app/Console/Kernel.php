<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
{
    // Generate invoices on the 1st of every month at 06:00
    $schedule->command('wcp:generate-invoices')
             ->monthlyOn(1, '06:00')
             ->appendOutputTo(storage_path('logs/invoices.log'));

    // Apply penalties daily at midnight (checks grace period internally)
    $schedule->command('wcp:apply-penalties')
             ->dailyAt('00:01');

    // Daily collection summary email to managers
    $schedule->command('wcp:daily-summary')
             ->dailyAt('20:00');

    // Weekly backup
    $schedule->command('backup:run')
             ->weekly()->sundays()->at('02:00');
}

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
