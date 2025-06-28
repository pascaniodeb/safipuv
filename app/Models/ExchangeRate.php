<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExchangeRate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'currency',
        'rate_to_bs',
        'operation',
        'month',
        'sector_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Tasa Cambiaria')
            ->dontSubmitEmptyLogs();
    }

    /**
     * ✅ Obtener la tasa de cambio para un sector, moneda y operación en un mes específico
     */
    public static function getRateForSector(string $month, string $currency, string $operation, int $sectorId): ?float
    {
        return self::where('month', $month)
            ->where('currency', strtoupper($currency))
            ->where('operation', $operation)
            ->where('sector_id', $sectorId)
            ->value('rate_to_bs');
    }

    /**
     * ✅ Obtener la tasa general (última registrada en el mes) si no se especifica sector
     */
    public static function getLatestRate(string $currency, string $month): float
    {
        $rate = self::where('currency', strtoupper($currency))
            ->whereMonth('created_at', \Carbon\Carbon::createFromFormat('Y-m', $month)->month)
            ->whereYear('created_at', \Carbon\Carbon::createFromFormat('Y-m', $month)->year)
            ->latest('created_at')
            ->first();

        return $rate?->rate_to_bs ?? throw new \Exception("Tasa no registrada para {$currency} en {$month}");
    }

    /**
     * ✅ Registrar o actualizar la tasa por sector, mes, operación y moneda
     */
    public static function storeRate(string $month, string $currency, float $rate, string $operation, int $sectorId): self
    {
        return self::updateOrCreate(
            [
                'month' => $month,
                'currency' => strtoupper($currency),
                'operation' => $operation,
                'sector_id' => $sectorId,
            ],
            [
                'rate_to_bs' => $rate,
            ]
        );
    }
}