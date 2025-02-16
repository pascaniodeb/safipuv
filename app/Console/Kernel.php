<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define el schedule para los comandos de la aplicación.
     */
    protected function schedule(Schedule $schedule)
    {
        // Registrar el comando para ejecutar diariamente
        $schedule->command('pastors:update-data')->daily(); // Cambiar "daily" según tus necesidades
    }

    /**
     * Registra los comandos de consola para la aplicación.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}