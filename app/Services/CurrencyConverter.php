<?php

namespace App\Services;

use App\Models\ExchangeRate;

class CurrencyConverter
{
    /**
     * Convierte un monto de una moneda a Bolívares.
     *
     * @param string $currency Código de la moneda (USD, COP, VES)
     * @param float $amount Monto a convertir
     * @return float Monto convertido a Bolívares
     * @throws \Exception
     */
    public static function convertToBs($currency, $amount)
    {
        if ($currency === 'VES') {
            return $amount; // Sin conversión
        }

        $rate = ExchangeRate::where('currency', $currency)->first();

        if (!$rate) {
            throw new \Exception("No se encontró la tasa de cambio para {$currency}.");
        }

        if ($rate->operation === '*') {
            return $amount * $rate->rate_to_bs;
        } elseif ($rate->operation === '/') {
            return $amount / $rate->rate_to_bs;
        }

        throw new \Exception("Operación inválida para la tasa de cambio de {$currency}.");
    }
}