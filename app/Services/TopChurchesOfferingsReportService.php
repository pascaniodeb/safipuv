<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TopChurchesOfferingsReportService
{
    private const CATEGORIAS = [
        1 => 'DIEZMOS',
        2 => 'EL PODER DEL UNO',
        3 => 'SEDE NACIONAL',
        7 => 'ÚNICA SECTORIAL',
    ];

    public function obtenerTopIglesias(
        string $periodo,
        string $categoriaNombre,
        ?string $referencia = null
    ): Collection {
        // Normaliza la entrada
        $categoriaNombreNormalizada = $this->normalizar($categoriaNombre);

        // Encuentra la categoría correspondiente
        $categoriaId = null;
        foreach (self::CATEGORIAS as $id => $nombreOriginal) {
            if ($this->normalizar($nombreOriginal) === $categoriaNombreNormalizada) {
                $categoriaId = $id;
                break;
            }
        }

        if (is_null($categoriaId)) {
            throw new \InvalidArgumentException("Categoría inválida: {$categoriaNombre}");
        }

        if (!in_array($periodo, ['mes', 'trimestre', 'semestre', 'anual'])) {
            throw new \InvalidArgumentException("Período inválido.");
        }

        switch ($periodo) {
            case 'mes':
                if (!preg_match('/^\d{4}-\d{2}$/', $referencia)) {
                    throw new \InvalidArgumentException("Referencia inválida. Debe ser en formato 'YYYY-MM' (ej: 2025-05).");
                }
                break;

            case 'trimestre':
                if (!preg_match('/^\d{4}-T[1-4]$/', $referencia)) {
                    throw new \InvalidArgumentException("Referencia inválida. Use el formato 'YYYY-T1' a 'YYYY-T4' (ej: 2025-T2).");
                }
                break;

            case 'semestre':
                if (!preg_match('/^\d{4}-S[1-2]$/', $referencia)) {
                    throw new \InvalidArgumentException("Referencia inválida. Use el formato 'YYYY-S1' o 'YYYY-S2' (ej: 2025-S1).");
                }
                break;

            case 'anual':
                if (!preg_match('/^\d{4}$/', $referencia)) {
                    throw new \InvalidArgumentException("Referencia inválida. Use solo el año (ej: 2025).");
                }
                break;
        }


        [$fechaInicio, $fechaFin] = $this->determinarRangoFechas($periodo, $referencia);

        return DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->join('churches', 'offering_reports.church_id', '=', 'churches.id')
            ->join('sectors', 'offering_reports.sector_id', '=', 'sectors.id')
            ->where('offering_items.offering_category_id', $categoriaId)
            ->whereBetween('offering_reports.month', [$fechaInicio->format('Y-m'), $fechaFin->format('Y-m')])
            ->where('offering_reports.status', 'aprobado')
            ->select(
                'churches.id as church_id',
                'churches.name as church_nombre',
                'sectors.id as sector_id',
                'sectors.name as sector_nombre',
                DB::raw('SUM(offering_items.subtotal_bs) as monto')
            )
            ->groupBy('churches.id', 'churches.name', 'sectors.id', 'sectors.name')
            ->orderByDesc('monto')
            ->limit(200)
            ->get()
            ->map(fn ($item) => (array) $item);
    }

    private function determinarRangoFechas(string $periodo, ?string $referencia): array
    {
        $hoy = Carbon::now();

        return match ($periodo) {
            'mes' => [
                Carbon::createFromFormat('Y-m', $referencia)->startOfMonth(),
                Carbon::createFromFormat('Y-m', $referencia)->startOfMonth(),
            ],

            'trimestre' => (function () use ($referencia) {
                // Ejemplo: 2025-T2
                [$year, $quarter] = explode('-T', $referencia);
                $monthStart = (($quarter - 1) * 3) + 1;
                $start = Carbon::create($year, $monthStart, 1)->startOfMonth();
                $end = $start->copy()->addMonths(2)->startOfMonth();
                return [$start, $end];
            })(),

            'semestre' => (function () use ($referencia) {
                // Ejemplo: 2025-S1
                [$year, $sem] = explode('-S', $referencia);
                $monthStart = ($sem == '1') ? 1 : 7;
                $start = Carbon::create($year, $monthStart, 1)->startOfMonth();
                $end = $start->copy()->addMonths(5)->startOfMonth();
                return [$start, $end];
            })(),

            'anual' => [
                Carbon::createFromFormat('Y', $referencia)->startOfYear(),
                Carbon::createFromFormat('Y', $referencia)->endOfYear()->startOfMonth(),
            ],

            default => throw new \InvalidArgumentException("Período inválido."),
        };
    }


    public static function categoriasDisponibles(): array
    {
        return self::CATEGORIAS;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto);
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i',
            'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'ü' => 'u',
        ]);
        return preg_replace('/[\s\-_]+/', '', $texto);
    }
}