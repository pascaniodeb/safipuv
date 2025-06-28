<?php

// app/Filament/Widgets/ExchangeRateWidget.php (Versión Mejorada)

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Models\ExchangeRate;
use App\Jobs\UpdateExchangeRatesJob;
use Filament\Notifications\Notification;

class ExchangeRateWidget extends Widget
{
    protected static string $view = 'filament.widgets.exchange-rate-widget';
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1
    ];
    
    protected static ?int $sort = 1;
    
    // 🔹 Aumenté el polling para no saturar el servidor
    protected static ?string $pollingInterval = '10m';

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->hasAnyRole([
            'Administrador',
            'Tesorero Nacional',
            'Directivo Nacional',
            'Tesorero Regional',
            'Supervisor Distrital',
            'Tesorero Sectorial',
        ]);
    }

    protected function getViewData(): array
    {
        $exchangeData = $this->getExchangeRates();
        
        return [
            'exchangeData' => $exchangeData,
            'userRole' => Auth::user()->role,
            'lastUpdated' => $exchangeData['date'] ?? date('d/m/Y'),
            'hasData' => $exchangeData['usd_rate'] > 0 || $exchangeData['cop_rate'] > 0,
            'isToday' => $this->isDataFromToday($exchangeData),
            'isWeekend' => $this->isWeekend(),
            'nextUpdateTime' => $this->getNextUpdateTime(),
            'canForceUpdate' => Auth::user()->hasAnyRole(['Administrador', 'Tesorero Nacional']),
        ];
    }

    private function getExchangeRates(): array
    {
        $cacheKey = Config::get('exchange_rates.cache.key_prefix', 'safipuv_exchange_rates_') . 'dashboard';
        $cacheTtl = Config::get('exchange_rates.cache.ttl', 1800);
        
        return Cache::remember($cacheKey, $cacheTtl, function () {
            $rates = ExchangeRate::whereNull('sector_id')
                                ->whereNull('month')
                                ->whereIn('operation', ['=', '/', '*'])
                                ->whereIn('currency', ['VES', 'USD', 'COP'])
                                ->get()
                                ->keyBy('currency');

            $exchangeData = [
                'usd_rate' => 0,
                'cop_rate' => 0,
                'ves_rate' => 1.00,
                'usd_operation' => '/',
                'cop_operation' => '*',
                'ves_operation' => '=',
                'date' => date('d/m/Y'),
                'last_update' => null,
            ];

            if ($rates->isNotEmpty()) {
                $lastUpdate = $rates->max('updated_at');
                
                if ($rates->has('USD')) {
                    $usd = $rates['USD'];
                    $exchangeData['usd_rate'] = (float) $usd->rate_to_bs;
                    $exchangeData['usd_operation'] = $usd->operation;
                }

                if ($rates->has('COP')) {
                    $cop = $rates['COP'];
                    $exchangeData['cop_rate'] = (float) $cop->rate_to_bs;
                    $exchangeData['cop_operation'] = $cop->operation;
                }

                if ($rates->has('VES')) {
                    $ves = $rates['VES'];
                    $exchangeData['ves_rate'] = (float) $ves->rate_to_bs;
                    $exchangeData['ves_operation'] = $ves->operation;
                }

                $exchangeData['date'] = $lastUpdate ? $lastUpdate->format('d/m/Y') : date('d/m/Y');
                $exchangeData['last_update'] = $lastUpdate;
            }

            return $exchangeData;
        });
    }

    private function isDataFromToday(array $exchangeData): bool
    {
        if (!isset($exchangeData['last_update']) || !$exchangeData['last_update']) {
            return false;
        }
        
        return $exchangeData['last_update']->isToday();
    }

    private function isWeekend(): bool
    {
        $today = now()->dayOfWeek;
        return $today === 0 || $today === 6; // Domingo = 0, Sábado = 6
    }

    private function getNextUpdateTime(): string
    {
        $now = now();
        
        if ($this->isWeekend()) {
            // Si es fin de semana, próxima actualización es el lunes
            $nextMonday = $now->next('Monday')->setTime(9, 0);
            return $nextMonday->format('l d/m/Y \a \l\a\s H:i');
        }
        
        $today9am = $now->copy()->setTime(9, 0);
        
        if ($now->lt($today9am)) {
            // Si aún no son las 9 AM de hoy
            return $today9am->format('H:i \d\e \h\o\y');
        } else {
            // Si ya pasaron las 9 AM, próxima actualización mañana
            $tomorrow9am = $now->addDay()->setTime(9, 0);
            if ($tomorrow9am->isWeekend()) {
                $nextMonday = $tomorrow9am->next('Monday')->setTime(9, 0);
                return $nextMonday->format('l d/m/Y \a \l\a\s H:i');
            }
            return $tomorrow9am->format('H:i \d\e \m\a\ñ\a\n\a');
        }
    }

    // 🔹 ACTUALIZACIÓN MANUAL MEJORADA
    public function refreshRates(): void
    {
        try {
            // Despachar job de actualización
            UpdateExchangeRatesJob::dispatch();
            
            // Limpiar caché usando la configuración
            $cacheKey = Config::get('exchange_rates.cache.key_prefix', 'safipuv_exchange_rates_') . 'dashboard';
            Cache::forget($cacheKey);
            
            Notification::make()
                ->title('Actualización iniciada')
                ->success()
                ->body('Las tasas se están actualizando automáticamente desde la fuentes oficial (Banco Central de Venezuela).')
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al actualizar')
                ->danger()
                ->body('No se pudo iniciar la actualización automática. Contacte al administrador.')
                ->send();
        }
    }

    // 🔹 FORZAR ACTUALIZACIÓN (solo para admin y tesorero nacional)
    public function forceUpdate(): void
    {
        if (!Auth::user()->hasAnyRole(['Administrador', 'Tesorero Nacional'])) {
            Notification::make()
                ->title('Acceso denegado')
                ->warning()
                ->body('No tiene permisos para forzar actualizaciones.')
                ->send();
            return;
        }

        try {
            // Ejecutar sincrónicamente para respuesta inmediata
            UpdateExchangeRatesJob::dispatchSync();
            
            $cacheKey = Config::get('exchange_rates.cache.key_prefix', 'safipuv_exchange_rates_') . 'dashboard';
            Cache::forget($cacheKey);
            
            Notification::make()
                ->title('Tasas actualizadas')
                ->success()
                ->body('Las tasas han sido actualizadas forzosamente desde las fuentes oficiales.')
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error en actualización forzada')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }
}