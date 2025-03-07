<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PastorLicenceService
{
    /**
     * Determina la licencia pastoral basada en las reglas establecidas.
     *
     * @param  int|null  $pastorIncomeId
     * @param  int|null  $pastorTypeId
     * @param  string|null  $startDate
     * @return int|null
     */
    public static function determineLicence(?int $pastorIncomeId, ?int $pastorTypeId, ?string $startDate): ?int
    {
        // 📌 1️⃣ Si el usuario tiene permiso, puede asignar manualmente
        if (Auth::user()?->hasAnyRole(['Administrador', 'Secretario Nacional'])) {
            return request('pastor_licence_id') ?: self::calculateLicence($pastorIncomeId, $pastorTypeId, $startDate);
        }

        // 📌 2️⃣ Aplicar reglas de negocio para calcular automáticamente la licencia
        return self::calculateLicence($pastorIncomeId, $pastorTypeId, $startDate);
    }

    /**
     * Calcula la licencia basada en los días en el ministerio y las reglas de tipo de pastor.
     *
     * @param  int|null  $pastorIncomeId
     * @param  int|null  $pastorTypeId
     * @param  string|null  $startDate
     * @return int|null
     */
    private static function calculateLicence(?int $pastorIncomeId, ?int $pastorTypeId, ?string $startDate): ?int
    {
        if (!$startDate || !strtotime($startDate)) {
            return null; // Si no hay fecha, no asigna licencia
        }

        $startDate = Carbon::parse($startDate)->startOfDay();
        $daysInMinistry = $startDate->diffInDays(now()->startOfDay());

        // 📌 Casos especiales
        if ($pastorIncomeId === 3 && $pastorTypeId === 4) {
            return 2; // Viuda - Pastora Titular siempre tiene NACIONAL (ID: 2)
        }

        if ($pastorIncomeId === 2 && in_array($pastorTypeId, [1, 2])) {
            // Titular o Adjunto con Código 141 → Comienza en NACIONAL
            return $daysInMinistry > 2100 ? 3 : 2;
        }

        if ($pastorIncomeId === 1 && $pastorTypeId === 3) {
            // Asistente (Regular) → Solo hasta NACIONAL
            return $daysInMinistry > 1005 ? 2 : 1;
        }

        // 📌 Regla General: Basado en fecha de inicio del ministerio
        return match (true) {
            $daysInMinistry <= 1005 => 1, // LOCAL
            $daysInMinistry > 1005 && $daysInMinistry <= 2100 => 2, // NACIONAL
            $daysInMinistry > 2100 => 3, // ORDENACIÓN
            default => null
        };
    }
}