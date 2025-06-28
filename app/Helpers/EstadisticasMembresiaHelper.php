<?php

namespace App\Helpers;

use App\Models\Church;
use App\Models\Region;
use App\Models\District;
use App\Models\Sector;
use App\Models\PastorMinistry;
use Illuminate\Support\Collection;

class EstadisticasMembresiaHelper
{
    public static function getCuadroMembresia(array $data = []): array
    {
        $query = Church::query();

        // Aplicar filtros si existen
        if (!empty($data['region_id'])) {
            $query->where('region_id', $data['region_id']);
        }
        if (!empty($data['district_id'])) {
            $query->where('district_id', $data['district_id']);
        }
        if (!empty($data['sector_id'])) {
            $query->where('sector_id', $data['sector_id']);
        }

        // Sumar los campos
        $totals = $query->selectRaw('
            SUM(adults) AS adults,
            SUM(children) AS children,
            SUM(members) AS members,
            SUM(baptized) AS baptized,
            SUM(to_baptize) AS to_baptize,
            SUM(holy_spirit) AS holy_spirit,
            SUM(groups_cells) AS groups_cells,
            SUM(centers_preaching) AS centers_preaching
        ')->first();

        return [
            ['#', 'Descripción', 'Total'],
            [1, 'Adultos', $totals->adults ?? 0],
            [2, 'Niños', $totals->children ?? 0],
            [3, 'Miembros', $totals->members ?? 0],
            [4, 'Bautizados', $totals->baptized ?? 0],
            [5, 'Por Bautizar', $totals->to_baptize ?? 0],
            [6, 'Llenos del Espíritu Santo', $totals->holy_spirit ?? 0],
            [7, 'Grupos o Células', $totals->groups_cells ?? 0],
            [8, 'Centros de Predicación', $totals->centers_preaching ?? 0],
        ];
    }
}