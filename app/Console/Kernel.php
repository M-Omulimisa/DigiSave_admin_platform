<?php

namespace App\Console;

use App\Jobs\CalculateLoanBalance;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    // protected function schedule(Schedule $schedule)
    // {
    //     $schedule->command('send:meeting-reminders')->everyMinute();
    // }
    protected function schedule(Schedule $schedule)
{
    $schedule->command('send:meeting-reminders')->everyMinute();
}
// php artisan schedule:work

    // protected function schedule(Schedule $schedule)
    // {
    //     // $schedule->job(new CalculateLoanBalance())->monthly();
    //     // $schedule->command('inspire')->hourly();
    // }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
