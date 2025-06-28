<?php

namespace App\Helpers;

use App\Models\Church;
use App\Models\Region;
use App\Models\District;
use App\Models\Sector;
use App\Models\PastorMinistry;
use Illuminate\Support\Collection;

class EstadisticasIglesiasHelper
{
    public static function getCuadroIglesias(array $data): array
    {
        $queryBase = Church::query();

        // 🔹 Aplicar filtros si fueron enviados
        if (!empty($data['region_id'])) {
            $queryBase->where('region_id', $data['region_id']);
        }
        if (!empty($data['district_id'])) {
            $queryBase->where('district_id', $data['district_id']);
        }
        if (!empty($data['sector_id'])) {
            $queryBase->where('sector_id', $data['sector_id']);
        }

        // 🔹 Contar total de iglesias
        $totalIglesias = (clone $queryBase)->count();

        // 🔹 Iglesias con pastor asignado
        $iglesiasConPastor = (clone $queryBase)
        ->whereIn('id', function ($query) {
            $query->select('church_id')
                ->from('pastor_ministries')
                ->where('active', 1)  // Solo ministerios activos
                ->whereNotNull('church_id');
        })
        ->count();

        // 🔹 Iglesias sin pastor
        $iglesiasSinPastor = $totalIglesias - $iglesiasConPastor;

        // 🔹 Iglesias por categorías (usamos el campo 'category_church_id')
        $categorias = [
            7 => 'Iglesias en Categoría A1 (más de 601 miembros)', // ID 7
            6 => 'Iglesias en Categoría A (de 501 a 600 miembros)', // ID 6
            5 => 'Iglesias en Categoría B1 (de 251 a 500 miembros)', // ID 5
            4 => 'Iglesias en Categoría B (de 126 a 250 miembros)', // ID 4
            3 => 'Iglesias en Categoría C (de 76 a 125 miembros)',  // ID 3
            2 => 'Iglesias en Categoría D (de 26 a 75 miembros)',   // ID 2
            1 => 'Iglesias en Categoría E (de 1 a 25 miembros)',    // ID 1
        ];

        $categoriasCounts = [];
        foreach ($categorias as $id => $descripcion) {
            $categoriasCounts[] = [
                $descripcion,
                (clone $queryBase)->where('category_church_id', $id)->count(),
            ];
        }

        // 🔹 Iglesias legalizadas (asumiendo que tienes un campo booleano 'legalized' en 'churches')
        $iglesiasLegalizadas = (clone $queryBase)
            ->where('legalized', true)
            ->count();

        // 🔹 Preparar la estructura final
        $cuadro = [
            ['#', 'Descripción', 'Total'], // encabezados
            [1, 'Iglesias', $totalIglesias],
            [2, 'Iglesias con Pastor', $iglesiasConPastor],
            [3, 'Iglesias sin Pastor', $iglesiasSinPastor],
        ];

        $contador = 4;
        foreach ($categoriasCounts as [$descripcion, $cantidad]) {
            $cuadro[] = [$contador++, $descripcion, $cantidad];
        }

        $cuadro[] = [$contador, 'Iglesias Legalizadas ante la DGJIRC', $iglesiasLegalizadas];

        return $cuadro;
    }
}