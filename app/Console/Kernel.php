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
        // 👥 COMANDOS EXISTENTES DE PASTORES
        // Registrar el comando para ejecutar diariamente
        $schedule->command('pastors:update-data')->daily();

        // 🗓️ Sincronización de pastores con usuarios - Ejecutar cada semana (domingo a la medianoche por defecto)
        $schedule->command('sync:pastores-a-usuarios')->weekly();

        // 💱 NUEVOS COMANDOS DE TASAS DE CAMBIO
        // 🔹 ACTUALIZACIÓN DIARIA DE TASAS DE CAMBIO
        // Ejecutar de lunes a viernes a las 9:00 AM UTC-4 (13:00 UTC)
        $schedule->command('exchange-rates:update')
                ->dailyAt('09:00')
                ->weekdays()
                ->timezone('America/Caracas') // UTC-4
                ->onSuccess(function () {
                    \Log::info('✅ Scheduler: Tasas de cambio actualizadas exitosamente');
                })
                ->onFailure(function () {
                    \Log::error('❌ Scheduler: Falló la actualización de tasas de cambio');
                });

        // 🔹 VERIFICACIÓN ADICIONAL LOS LUNES
        // Por si el viernes no se ejecutó correctamente
        $schedule->command('exchange-rates:update --force')
                ->weekdays()
                ->mondays()
                ->at('09:05') // 5 minutos después del principal
                ->when(function () {
                    // Solo ejecutar si las tasas no son de hoy
                    $lastUpdate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                          ->whereNull('month')
                                                          ->where('currency', 'USD')
                                                          ->value('updated_at');
                    
                    return !$lastUpdate || !$lastUpdate->isToday();
                })
                ->description('Verificación de respaldo para tasas de cambio los lunes');

        // 🧹 LIMPIEZA OPCIONAL DE LOGS ANTIGUOS (solo si lo necesitas)
        $schedule->command('log:clear --days=30')
                ->weekly()
                ->sundays()
                ->at('02:00')
                ->description('Limpiar logs antiguos de más de 30 días');

        // 📊 OPCIONAL: Comando para generar reportes semanales (si lo implementas después)
        // $schedule->command('reports:weekly-exchange-rates')
        //         ->weekly()
        //         ->fridays()
        //         ->at('17:00')
        //         ->description('Generar reporte semanal de tasas de cambio');
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