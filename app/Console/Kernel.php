<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('summary:daily-report')->dailyAt('00:00')->appendOutputTo(storage_path('logs/reportes_automaticos.log'));
        $schedule->command('send:reporte-diario-operaciones')->dailyAt('17:00')->appendOutputTo(storage_path('logs/reportes_automaticos.log'));
        //$schedule->command('send:reporte-diario-operaciones')->dailyAt('14:00')->appendOutputTo(storage_path('logs/reportes_automaticos.log'));
        $schedule->command('send:reporte-diario-operaciones')->dailyAt('08:00')->appendOutputTo(storage_path('logs/reportes_automaticos.log'));
        $schedule->command('gps:actualizar')->everyTwoMinutes()->withoutOverlapping()->appendOutputTo(storage_path('logs/reportes_automaticos.log'));
        $schedule->command('viajes:check-alertas')->everyThirtyMinutes()->withoutOverlapping()->appendOutputTo(storage_path('logs/reportes_automaticos.log'));
        $schedule->call(function () {
            app(\App\Services\ReporteEficienciaService::class)->refrescarAgregados();
        })->hourly(); // Se actualiza cada hora automáticamente
        $schedule->command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
    }

 

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
