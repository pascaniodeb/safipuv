<?php

namespace App\Helpers;

use App\Models\Pastor;
use App\Models\Region;
use App\Models\District;
use App\Models\Sector;

class CuadernoElectoralHelper
{
    public static function getListado(array $data = []): array
    {
        $user = auth()->user();

        // 🔐 Detectar roles
        $isRegional = $user->hasRole([
            'Superintendente Regional',
            'Secretario Regional',
            'Tesorero Regional',
            'Contralor Regional',
            'Inspector Regional',
            'Directivo Regional',
        ]);

        $isDistrital = $user->hasRole([
            'Supervisor Distrital',
        ]);

        // 🧩 Filtros con fallback desde el usuario
        $regionId = $data['region_id'] ?? ($isRegional || $isDistrital ? $user->region_id : null);
        $districtId = $data['district_id'] ?? ($isDistrital ? $user->district_id : null);
        $sectorId = $data['sector_id'] ?? null;

        $query = Pastor::with(['pastorMinistry.licence', 'nationality'])
            ->whereHas('pastorMinistry', fn ($q) =>
                $q->whereIn('pastor_licence_id', [2, 3]) // Nacional y Ordenación
            );

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        if ($districtId) {
            $query->where('district_id', $districtId);
        }

        if ($sectorId) {
            $query->where('sector_id', $sectorId);
        }

        // Ordenar por número de cédula numéricamente
        $pastores = $query->get()->sortBy(function ($p) {
            return (int) preg_replace('/\D/', '', $p->number_cedula);
        });

        $rows = [];
        $nro = 1;

        foreach ($pastores as $pastor) {
            $ministry = $pastor->pastorMinistry;

            // Formatear cédula
            $cedulaNumerica = preg_replace('/\D/', '', $pastor->number_cedula);
            $cedulaFormateada = number_format((int) $cedulaNumerica, 0, '', '.');

            $cedulaFinal = match ($pastor->nationality_id) {
                1 => 'V-' . $cedulaFormateada,
                2 => 'E-' . $cedulaFormateada,
                default => $cedulaFormateada,
            };

            $rows[] = [
                $nro++,
                mb_strtoupper($pastor->lastname),
                mb_strtoupper($pastor->name),
                $cedulaFinal,
                $ministry?->code_pastor ?? '',
                mb_strtoupper(optional($ministry?->licence)->name ?? ''),
                '', // Espacio para registro de votos
            ];
        }

        return $rows;
    }


    public static function getUbicacion(array $data): array
    {
        $user = auth()->user();

        // Detectar roles
        $isRegional = $user->hasRole([
            'Superintendente Regional',
            'Secretario Regional',
            'Tesorero Regional',
            'Contralor Regional',
            'Inspector Regional',
            'Directivo Regional',
        ]);

        $isDistrital = $user->hasRole([
            'Supervisor Distrital',
        ]);

        // 📌 Asignar valores según selección o fallback por rol
        $regionId   = $data['region_id']   ?? ($isRegional || $isDistrital ? $user->region_id : null);
        $districtId = $data['district_id'] ?? ($isDistrital ? $user->district_id : null);
        $sectorId   = $data['sector_id']   ?? null;

        // 📌 Obtener nombres legibles o 'TODOS'
        $regionName   = $regionId   ? Region::find($regionId)?->name   ?? 'TODOS' : 'TODOS';
        $districtName = $districtId ? District::find($districtId)?->name ?? 'TODOS' : 'TODOS';
        $sectorName   = $sectorId   ? Sector::find($sectorId)?->name   ?? 'TODOS' : 'TODOS';

        return [
            'region'   => $regionName,
            'district' => $districtName,
            'sector'   => $sectorName,
        ];
    }

}