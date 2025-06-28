<?php

// app/Jobs/UpdateExchangeRatesJob.php

namespace App\Jobs;

use App\Models\ExchangeRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Exception;

class UpdateExchangeRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutos timeout
    public $tries = 3; // 3 intentos si falla

    public function handle(): void
    {
        try {
            Log::info('🔄 Iniciando actualización automática de tasas de cambio');

            // 1. Obtener tasa VES/USD desde la API del BCV
            $usdRate = $this->getVesUsdRate();
            
            if (!$usdRate) {
                throw new Exception('No se pudo obtener la tasa VES/USD del BCV');
            }

            // 2. Obtener tasa COP/USD desde la página de Colombia
            $copUsdRate = $this->getCopUsdRate();
            
            if (!$copUsdRate) {
                throw new Exception('No se pudo obtener la tasa COP/USD de Colombia');
            }

            // 3. Calcular tasa VES/COP (TRM ÷ VES/USD)
            $vesCopRate = $copUsdRate / $usdRate;

            // 4. Actualizar las tasas en la base de datos
            $this->updateExchangeRates([
                'usd_rate' => $usdRate,
                'cop_rate' => $vesCopRate
            ]);

            // 5. Limpiar caché para que el widget se actualice
            Cache::forget('dashboard_exchange_rates');

            Log::info('✅ Tasas actualizadas exitosamente', [
                'usd_rate' => $usdRate,
                'cop_usd_rate' => $copUsdRate,
                'ves_cop_rate' => $vesCopRate,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            Log::error('❌ Error actualizando tasas de cambio: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            
            // Re-lanzar la excepción para que Laravel maneje los reintentos
            throw $e;
        }
    }

    /**
     * Obtener tasa VES/USD desde la API de DolarAPI Venezuela
     */
    private function getVesUsdRate(): ?float
    {
        try {
            $config = Config::get('exchange_rates.venezuela_api');
            $response = Http::timeout($config['timeout'])->get($config['url']);
            
            if ($response->successful()) {
                $data = $response->json();
                $rateField = $config['rate_field'];
                $rate = (float) $data[$rateField] ?? null;
                
                if ($rate && $this->isValidUsdRate($rate)) {
                    Log::info('✅ Tasa VES/USD obtenida: ' . $rate, [
                        'fuente' => $data['fuente'] ?? 'DolarAPI',
                        'fecha_actualizacion' => $data['fechaActualizacion'] ?? now()->toISOString()
                    ]);
                    return $rate;
                }
                
                Log::warning('❌ Tasa VES/USD fuera del rango válido: ' . $rate);
                return null;
            }

            Log::warning('❌ API DolarVenezuela respondió con error: ' . $response->status());
            return null;

        } catch (Exception $e) {
            Log::error('❌ Error consultando API DolarVenezuela: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener tasa COP/USD desde la API oficial de datos.gov.co Colombia
     */
    private function getCopUsdRate(): ?float
    {
        try {
            $config = Config::get('exchange_rates.colombia_api');
            $today = now()->format('Y-m-d');
            $url = $config['url'] . '?' . $config['date_param'] . '=' . $today;
            
            $response = Http::timeout($config['timeout'])->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                $rateField = $config['rate_field'];
                
                if (!empty($data) && isset($data[0][$rateField])) {
                    $rate = (float) $data[0][$rateField];
                    
                    if ($rate && $this->isValidCopRate($rate)) {
                        Log::info('✅ Tasa TRM COP/USD obtenida: ' . $rate, [
                            'vigencia_desde' => $data[0]['vigenciadesde'] ?? 'No especificada',
                            'vigencia_hasta' => $data[0]['vigenciahasta'] ?? 'No especificada'
                        ]);
                        return $rate;
                    }
                }
                
                // Si no hay datos para hoy, intentar con el día anterior
                return $this->getCopUsdRateFallback($config);
            }

            Log::warning('❌ API TRM Colombia respondió con error: ' . $response->status());
            return null;

        } catch (Exception $e) {
            Log::error('❌ Error consultando TRM Colombia: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar las tasas en la base de datos
     */
    private function updateExchangeRates(array $rates): void
    {
        $now = now();

        // Actualizar USD
        ExchangeRate::updateOrCreate(
            [
                'currency' => 'USD',
                'sector_id' => null,
                'month' => null
            ],
            [
                'rate_to_bs' => $rates['usd_rate'],
                'operation' => '/',
                'updated_at' => $now
            ]
        );

        // Actualizar COP
        ExchangeRate::updateOrCreate(
            [
                'currency' => 'COP',
                'sector_id' => null,
                'month' => null
            ],
            [
                'rate_to_bs' => $rates['cop_rate'],
                'operation' => '*',
                'updated_at' => $now
            ]
        );

        // Mantener VES = 1 (valor base)
        ExchangeRate::updateOrCreate(
            [
                'currency' => 'VES',
                'sector_id' => null,
                'month' => null
            ],
            [
                'rate_to_bs' => 1.00,
                'operation' => '=',
                'updated_at' => $now
            ]
        );

        Log::info('💾 Tasas guardadas en base de datos correctamente');
    }

    /**
     * Método fallback para obtener TRM del día anterior
     */
    private function getCopUsdRateFallback(array $config): ?float
    {
        $yesterday = now()->subDay()->format('Y-m-d');
        $fallbackUrl = $config['url'] . '?' . $config['date_param'] . '=' . $yesterday;
        
        try {
            $response = Http::timeout($config['timeout'])->get($fallbackUrl);
            if ($response->successful()) {
                $data = $response->json();
                $rateField = $config['rate_field'];
                
                if (!empty($data) && isset($data[0][$rateField])) {
                    $rate = (float) $data[0][$rateField];
                    if ($this->isValidCopRate($rate)) {
                        Log::info('✅ Tasa TRM COP/USD obtenida (día anterior): ' . $rate);
                        return $rate;
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('❌ Error en fallback TRM: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Validar que la tasa USD esté en un rango realista
     */
    private function isValidUsdRate(float $rate): bool
    {
        $min = Config::get('exchange_rates.validation.min_usd_rate', 1);
        $max = Config::get('exchange_rates.validation.max_usd_rate', 1000);
        return $rate >= $min && $rate <= $max;
    }

    /**
     * Validar que la tasa COP esté en un rango realista
     */
    private function isValidCopRate(float $rate): bool
    {
        $min = Config::get('exchange_rates.validation.min_cop_rate', 1000);
        $max = Config::get('exchange_rates.validation.max_cop_rate', 10000);
        return $rate >= $min && $rate <= $max;
    }

    /**
     * Manejar fallas del job
     */
    public function failed(Exception $exception): void
    {
        Log::error('💥 Job de actualización de tasas falló definitivamente', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Opcional: enviar notificación a administradores
        // Notification::route('mail', 'admin@safipuv.com')
        //     ->notify(new ExchangeRateUpdateFailed($exception));
    }
}