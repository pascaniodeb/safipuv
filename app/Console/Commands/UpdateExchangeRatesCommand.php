<?php

// app/Console/Commands/UpdateExchangeRatesCommand.php

namespace App\Console\Commands;

use App\Jobs\UpdateExchangeRatesJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:update 
                          {--force : Forzar actualización incluso en fines de semana}
                          {--sync : Ejecutar sincrónicamente sin queue}';

    protected $description = 'Actualizar tasas de cambio VES/USD y VES/COP automáticamente';

    public function handle(): int
    {
        $this->info('🔄 Iniciando actualización de tasas de cambio...');

        // Verificar si es día laboral (lunes a viernes)
        if (!$this->option('force') && $this->isWeekend()) {
            $this->warn('⚠️  Es fin de semana. Use --force para forzar actualización.');
            return self::SUCCESS;
        }

        try {
            if ($this->option('sync')) {
                // Ejecutar sincrónicamente
                $this->info('⚡ Ejecutando actualización sincrónica...');
                UpdateExchangeRatesJob::dispatchSync();
            } else {
                // Ejecutar en queue
                $this->info('📋 Despachando job a la cola...');
                UpdateExchangeRatesJob::dispatch();
            }

            $this->info('✅ Actualización iniciada correctamente');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error en comando exchange-rates:update: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function isWeekend(): bool
    {
        $today = now()->dayOfWeek;
        return $today === 0 || $today === 6; // Domingo = 0, Sábado = 6
    }
}