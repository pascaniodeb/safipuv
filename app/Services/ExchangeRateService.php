<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Collection;

class ExchangeRateService
{
    /**
     * Obtiene las tasas de cambio por sector para el mes indicado.
     * Solo considera tasas de tipo 'C' (compra).
     *
     * @param Collection $sectores
     * @param string $month
     * @return array
     */
    public function tasasPorSector(Collection $sectores, string $month): array
    {
        return ExchangeRate::where('month', $month)
            ->whereIn('sector_id', $sectores->pluck('id'))
            ->where('operation', 'D')
            ->get()
            ->groupBy('sector_id')
            ->mapWithKeys(function ($tasas, $sectorId) {
                $tasasPorMoneda = $tasas->keyBy('currency');

                return [
                    (int) $sectorId => [
                        'usd_rate' => optional($tasasPorMoneda->get('USD'))->rate_to_bs,
                        'cop_rate' => optional($tasasPorMoneda->get('COP'))->rate_to_bs,
                    ],
                ];
            })
            ->toArray();
    }
}