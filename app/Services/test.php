<?php

namespace App\Services;

use App\Models\{
    Church, OfferingCategory, OfferingDistribution,
    OfferingReport, Treasury
};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SectorOfferingsReportService
{
    public function generate(int $sectorId, string $month, float $usdRate, float $copRate): string
    {
        // 🏛️ Iglesias del sector
        $churches = Church::where('sector_id', $sectorId)
            ->orderBy('name')
            ->with([
                'offeringReports' => fn ($query) => $query
                    ->where('month', $month)
                    ->with('pastor')
            ])
            ->get();

        // 📌 Reportes SIN iglesia pero con sector asignado
        $reportesSinIglesia = OfferingReport::whereNull('church_id')
            ->where('month', $month)
            ->where('sector_id', $sectorId)
            ->with('pastor')
            ->get();

        // 🧠 Combinar registros para la vista
        $registrosCombinados = collect();

        foreach ($churches as $church) {
            foreach ($church->offeringReports as $report) {
                $pastor = $report->pastor;
                $typeId = $pastor->type_id ?? null;

                $etiqueta = match ($typeId) {
                    2 => ' (PASTOR ADJUNTO)',
                    3 => ' (PASTOR ASISTENTE)',
                    default => '',
                };

                $nombre = $church->name . $etiqueta;

                $registrosCombinados->push([
                    'nombre' => $nombre,
                    'church_id' => $church->id,
                    'report' => $report,
                ]);
            }
        }

        foreach ($reportesSinIglesia as $report) {
            $pastor = $report->pastor;
            $nombrePastor = $pastor?->full_name ?? 'Desconocido';

            $registrosCombinados->push([
                'nombre' => 'SIN IGLESIA',
                'church_id' => null,
                'report' => $report,
                'pastor_name' => $nombrePastor,
            ]);
        }


        // 🧮 Totales por reporte
        $reportIds = $registrosCombinados->pluck('report.id')->filter()->unique();
        $totales = $this->totalesPorReporte($reportIds);
        $totalesGenerales = $this->totalesGeneralesPorReporte($reportIds);

        // 🔎 Convención detectada
        $convencionId = DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.church_id', $churches->pluck('id'))
            ->where('offering_reports.month', $month)
            ->whereIn('offering_items.offering_category_id', [4, 5, 6])
            ->select('offering_items.offering_category_id')
            ->groupBy('offering_items.offering_category_id')
            ->orderBy('offering_items.offering_category_id')
            ->value('offering_items.offering_category_id');

        $convencionNombre = match ($convencionId) {
            4 => 'DISTRITAL',
            5 => 'REGIONAL',
            6 => 'NACIONAL',
            default => null,
        };

        $resumenCategorias = $this->resumenPorCategoria($churches->pluck('id'), $month);
        $resumenDeducciones = $this->resumenDeduccionesPorNivel($resumenCategorias);

        $ingresosGlobales = DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.church_id', $churches->pluck('id'))
            ->where('offering_reports.month', $month)
            ->selectRaw('offering_items.offering_category_id, SUM(offering_items.subtotal_bs) as total')
            ->groupBy('offering_items.offering_category_id')
            ->pluck('total', 'offering_items.offering_category_id');

        $deduccionesPorNivel = DB::table('treasury_allocations')
            ->join('offering_reports', 'treasury_allocations.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.church_id', $churches->pluck('id'))
            ->where('treasury_allocations.month', $month)
            ->selectRaw('treasury_allocations.treasury_id, SUM(treasury_allocations.amount) as total')
            ->groupBy('treasury_allocations.treasury_id')
            ->pluck('total', 'treasury_allocations.treasury_id');

        $mapaTesorerias = Treasury::pluck('name', 'id');

        // 📄 Data para la vista
        $data = [
            'registrosCombinados' => $registrosCombinados,
            'sectorNombre' => $churches->first()?->sector->name ?? 'Todos los Sectores',
            'totales' => $totales,
            'totalesGenerales' => $totalesGenerales,
            'convencionId' => $convencionId,
            'convencionNombre' => $convencionNombre,
            'month' => $month,
            'usdRate' => $usdRate,
            'copRate' => $copRate,
            'resumenCategorias' => $resumenCategorias,
            'resumenDeducciones' => $resumenDeducciones,
            'ingresosGlobales' => $ingresosGlobales,
            'deduccionesPorNivel' => $deduccionesPorNivel,
            'mapaTesorerias' => $mapaTesorerias,
            'deducciones' => [
                'tasas' => [
                    'usd_to_bs' => $usdRate,
                    'cop_to_bs' => $copRate,
                ]
            ]
        ];

        return Pdf::loadView('pdfs.sector-offerings-report', $data)->output();
    }

    private function totalesPorReporte(Collection $reportIds): Collection
    {
        return DB::table('offering_items')
            ->whereIn('offering_report_id', $reportIds)
            ->selectRaw('offering_report_id, offering_category_id, SUM(subtotal_bs) as total_bs')
            ->groupBy('offering_report_id', 'offering_category_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->offering_report_id . '.' . $row->offering_category_id => $row->total_bs,
            ]);
    }

    private function totalesGeneralesPorReporte(Collection $reportIds): Collection
    {
        return DB::table('offering_reports')
            ->whereIn('id', $reportIds)
            ->selectRaw('id, SUM(grand_total_bs) as total_bs')
            ->groupBy('id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->id => $row->total_bs]);
    }

    private function resumenPorCategoria(Collection $churchIds, string $month): array
    {
        return DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.church_id', $churchIds)
            ->where('offering_reports.month', $month)
            ->select('offering_items.offering_category_id', DB::raw('SUM(offering_items.subtotal_bs) as total'))
            ->groupBy('offering_items.offering_category_id')
            ->pluck('total', 'offering_category_id')
            ->toArray();
    }

    private function resumenDeduccionesPorNivel(array $categoriasTotales): array
    {
        $distribuciones = OfferingDistribution::with('targetTreasury')->get();
        $agrupado = [];

        foreach ($distribuciones as $dist) {
            $nivel = strtolower($dist->targetTreasury->level ?? 'otro');
            $catId = $dist->offering_category_id;
            $montoBase = $categoriasTotales[$catId] ?? 0;
            $monto = $montoBase * ($dist->percentage / 100);

            $agrupado[$nivel][] = [
                'categoria_id' => $catId,
                'categoria_nombre' => OfferingCategory::find($catId)?->name ?? '—',
                'porcentaje' => $dist->percentage,
                'monto' => $monto,
            ];
        }

        return $agrupado;
    }
}