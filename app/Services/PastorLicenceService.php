<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PastorLicenceService
{
    /**
     * Determina la licencia pastoral basándose únicamente en:
     *  - Tipo de pastor ($pastorTypeId)
     *  - Días de ministerio (derivados de $startDateMinistry)
     */
    public static function calculateLicence(?int $pastorTypeId, ?string $startDateMinistry): ?int
    {
        if (! $startDateMinistry) {
            return null; 
        }

        // Si no hay fecha o no es válida, retornar null
        if (! $startDateMinistry || ! strtotime($startDateMinistry)) {
            return null;
        }

        // Calcular días transcurridos
        $daysInMinistry = Carbon::parse($startDateMinistry)
            ->startOfDay()
            ->diffInDays(now()->startOfDay());

        switch ($pastorTypeId) {
            // (4) Pastora Titular => siempre licencia NACIONAL
            case 4:
                return 2; // NACIONAL

            // (3) Asistente => inicia con LOCAL (si < 1000 días),
            //                   después (>= 1000) pasa a NACIONAL y ahí se queda
            case 3:
                return $daysInMinistry < 1000
                    ? 1 // LOCAL
                    : 2; // NACIONAL

            // (2) Adjunto => comienza con NACIONAL, y tras 1000 días pasa a ORDENACIÓN
            case 2:
                return $daysInMinistry < 1000
                    ? 2 // NACIONAL
                    : 3; // ORDENACIÓN

            // (1) Titular => va por los 3 escalones:
            //   - < 1000 => LOCAL
            //   - [1000..2100] => NACIONAL
            //   - > 2100 => ORDENACIÓN
            case 1:
                if ($daysInMinistry < 1000) {
                    return 1; // LOCAL
                } elseif ($daysInMinistry < 2101) {
                    return 2; // NACIONAL
                } else {
                    return 3; // ORDENACIÓN
                }

            // Cualquier otro tipo (o nulo) => sin licencia
            default:
                return null;
        }
    }

    public static function determineLicence(?int $pastorTypeId, ?string $startDate): ?int
    {
        // Si el usuario actual PUEDE asignar manualmente y el request trae un pastor_licence_id,
        // entonces devolvemos lo que el usuario seleccionó. Sino, calculamos automáticamente.
        if (auth()->user()->hasAnyRole(['Administrador','Secretario Nacional'])) {
            return request('pastor_licence_id') 
                ?: \App\Services\PastorLicenceService::calculateLicence($pastorTypeId, $startDate);
        }

        // Para roles sin permiso, siempre calculamos.
        return \App\Services\PastorLicenceService::calculateLicence($pastorTypeId, $startDate);
    }

}