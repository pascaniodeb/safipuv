<?php

namespace App\Services;

use App\Models\{Church, OfferingCategory, OfferingDistribution};
use App\Services\ExchangeRateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DistrictOfferingsReportService
{
    private function obtenerSectores(int $districtId): Collection
    {
        return DB::table('sectors')
            ->where('district_id', $districtId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function generarTotalesPorSectorConDeduccionesDistritales(Collection $sectores, string $month): Collection
    {
        $categoriaIds = [1, 2, 4]; // DIEZMOS, PODER DEL UNO, CONVENCIÓN DISTRITAL

        $resultados = DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.sector_id', $sectores->pluck('id'))
            ->where('offering_reports.month', $month)
            ->whereIn('offering_items.offering_category_id', $categoriaIds)
            ->select(
                'offering_reports.sector_id',
                'offering_items.offering_category_id',
                DB::raw('SUM(offering_items.subtotal_bs) as total_bs')
            )
            ->groupBy('offering_reports.sector_id', 'offering_items.offering_category_id')
            ->get();

        $agrupado = [];
        foreach ($resultados as $item) {
            $agrupado[$item->sector_id][$item->offering_category_id] = $item->total_bs;
        }

        return $sectores->map(function ($sector) use ($agrupado) {
            return [
                'sector_id'     => $sector->id,
                'sector_nombre' => $sector->name,
                'totales'       => $agrupado[$sector->id] ?? [],
            ];
        });
    }

    private function obtenerTasasPorSector(Collection $sectores, string $month): array
    {
        return app(ExchangeRateService::class)->tasasPorSector($sectores, $month);
    }

    private function calcularDeduccionDistritalPorSector(Collection $sectores, string $month): array
    {
        $categoriaIds = [1, 2, 4];

        $items = DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.sector_id', $sectores->pluck('id'))
            ->where('offering_reports.month', $month)
            ->where('offering_reports.status', 'aprobado')
            ->whereIn('offering_items.offering_category_id', $categoriaIds)
            ->select(
                'offering_reports.sector_id',
                'offering_items.offering_category_id',
                DB::raw('SUM(offering_items.subtotal_bs) as total_bs')
            )
            ->groupBy('offering_reports.sector_id', 'offering_items.offering_category_id')
            ->get();

        $tasas = $this->obtenerTasasPorSector($sectores, $month);

        $distribuciones = OfferingDistribution::with('targetTreasury')
            ->whereIn('offering_category_id', $categoriaIds)
            ->get()
            ->filter(fn ($d) => strtolower($d->targetTreasury->level ?? '') === 'distrital')
            ->keyBy('offering_category_id');

        $nombres = OfferingCategory::whereIn('id', $categoriaIds)->pluck('name', 'id');

        $resultado = [];

        foreach ($items as $item) {
            $sectorId = $item->sector_id;
            $catId = $item->offering_category_id;

            $tasaUsd = $tasas[$sectorId]['usd_rate'] ?? null;
            $tasaCop = $tasas[$sectorId]['cop_rate'] ?? null;
            $porcentaje = $distribuciones[$catId]->percentage ?? 0;

            $deducidoBs = $item->total_bs * ($porcentaje / 100);
            $deducidoUsd = $tasaUsd ? $deducidoBs / $tasaUsd : 0;
            $deducidoCop = $tasaCop ? $deducidoBs * $tasaCop : 0;

            $resultado[] = [
                'sector_id'        => $sectorId,
                'sector_nombre'    => $sectores->firstWhere('id', $sectorId)?->name ?? '—',
                'categoria_id'     => $catId,
                'categoria_nombre' => $nombres[$catId] ?? '—',
                'porcentaje'       => $porcentaje,
                'monto_bs'         => $deducidoBs,
                'monto_usd'        => $deducidoUsd,
                'monto_cop'        => $deducidoCop,
            ];
        }

        return $resultado;
    }

    public function generate(int $districtId, string $month, float $usdRate, float $copRate): string
    {
        $district = \App\Models\District::find($districtId);
        
        $districtNombre = $district?->name ?? 'Distrito no encontrado';
        
        $sectores = $this->obtenerSectores($districtId);

        $sectoresConTotales = $this->generarTotalesPorSectorConDeduccionesDistritales($sectores, $month);

        $deduccionesDistritales = $this->calcularDeduccionDistritalPorSector($sectores, $month);

        $sectoresConTasas = $this->obtenerTasasPorSector($sectores, $month); // ✅ Uso único del servicio central

        $data = [
            'month' => $month,
            'usdRate' => $usdRate,
            'copRate' => $copRate,
            'sectoresConTotales' => $sectoresConTotales,
            'deduccionesDistritales' => $deduccionesDistritales,
            'sectoresConTasas' => $sectoresConTasas, // 💡 Pasado al Blade como [sector_id => tasas]
            'districtNombre' => $districtNombre,
        ];

        return Pdf::loadView('pdfs.district-offerings-report', $data)->output();
    }
}