<?php

namespace App\Helpers;

use App\Models\Pastor;

class EstadisticasPastoresHelper
{
    public static function getCuadroPastores(array $data = []): array
    {
        // Base query con relaciones
        $queryBase = Pastor::query();

        // Aplicar filtros si existen
        if (!empty($data['region_id'])) {
            $queryBase->where('region_id', $data['region_id']);
        }

        if (!empty($data['district_id'])) {
            $queryBase->where('district_id', $data['district_id']);
        }

        if (!empty($data['sector_id'])) {
            $queryBase->where('sector_id', $data['sector_id']);
        }

        return [
            ['#', 'Descripción', 'Total'],
            [1, 'Pastores', (clone $queryBase)->count()],
            [2, 'Pastores Ordenados', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_licence_id', 3))->count()],
            [3, 'Pastores Nacionales', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_licence_id', 2))->count()],
            [4, 'Pastores Locales', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_licence_id', 1))->count()],
            [5, 'Pastores Titulares', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_type_id', 1))->count()],
            [6, 'Pastores Adjuntos', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_type_id', 2))->count()],
            [7, 'Pastores Asistentes', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_type_id', 3))->count()],
            [8, 'Pastoras Titulares', (clone $queryBase)->whereHas('pastorMinistry', function ($q) {
                $q->where('pastor_type_id', 1)
                  ->whereHas('pastor', fn($sub) => $sub->where('gender_id', 2));
            })->count()],
            [9, 'Pastores Nivel Bronce', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_level_id', 1))->count()],
            [10, 'Pastores Nivel Plata', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_level_id', 2))->count()],
            [11, 'Pastores Nivel Titanio', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_level_id', 3))->count()],
            [12, 'Pastores Nivel Oro', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_level_id', 4))->count()],
            [13, 'Pastores Nivel Platino', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_level_id', 5))->count()],
            [14, 'Pastores Nivel Platino Plus', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_level_id', 6))->count()],
            [15, 'Pastores Nivel Diamante', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_level_id', 7))->count()],
            [16, 'Pastores Nivel Zafiro', (clone $queryBase)->whereHas('pastorMinistry', fn($q) => $q->where('pastor_level_id', 8))->count()],
            [17, 'Pastores Sin Iglesia', (clone $queryBase)->whereDoesntHave('pastorMinistry', fn($q) => $q->whereNotNull('church_id'))->count()],
        ];
    }
}