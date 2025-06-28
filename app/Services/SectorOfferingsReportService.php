<?php

namespace App\Services;

use App\Models\{
    Church, OfferingCategory, OfferingDistribution,
    OfferingReport, Treasury, Pastor, PastorMinistry
};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SectorOfferingsReportService
{
    public function generate(int $sectorId, string $month, float $usdRate, float $copRate): string
    {
        // 🔧 NUEVO: Verificar y actualizar automáticamente offering_reports
        $this->ensureOfferingReportsAreUpdated($sectorId, $month);
        
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
            ->whereIn('offering_reports.id', $reportIds)
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

        // 🔧 CORREGIDO: Pasar reportIds en lugar de solo church IDs
        $resumenCategorias = $this->resumenPorCategoria($reportIds, $month);
        $resumenDeducciones = $this->resumenDeduccionesPorNivel($resumenCategorias);

        $ingresosGlobales = DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.id', $reportIds)
            ->where('offering_reports.month', $month)
            ->selectRaw('offering_items.offering_category_id, SUM(offering_items.subtotal_bs) as total')
            ->groupBy('offering_items.offering_category_id')
            ->pluck('total', 'offering_items.offering_category_id');

        $deduccionesPorNivel = DB::table('treasury_allocations')
            ->join('offering_reports', 'treasury_allocations.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.id', $reportIds)
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

    /**
     * 🔧 NUEVO MÉTODO: Verifica y actualiza automáticamente offering_reports
     * si hay discrepancias de ubicación
     */
    private function ensureOfferingReportsAreUpdated(int $sectorId, string $month): void
    {
        // Buscar reportes que potencialmente necesitan actualización
        $reportsToCheck = OfferingReport::withoutGlobalScopes() // Evitar filtros de seguridad
            ->with(['pastor', 'church'])
            ->where('month', $month)
            ->where(function($query) use ($sectorId) {
                $query->where('sector_id', $sectorId)
                      ->orWhereHas('pastor', function($q) use ($sectorId) {
                          $q->where('sector_id', $sectorId);
                      })
                      ->orWhereHas('church', function($q) use ($sectorId) {
                          $q->where('sector_id', $sectorId);
                      });
            })
            ->get();

        foreach ($reportsToCheck as $report) {
            $this->updateReportLocationIfNeeded($report);
        }
    }

    /**
     * 🔧 NUEVO MÉTODO: Actualiza ubicación de reporte si es necesario
     */
    private function updateReportLocationIfNeeded(OfferingReport $report): void
    {
        $pastor = $report->pastor;
        $church = $report->church;

        if (!$pastor) {
            return;
        }

        $shouldUpdate = false;
        $newLocation = [];

        if ($church) {
            // Reporte CON iglesia: usar ubicación de la iglesia
            if ($report->region_id !== $church->region_id ||
                $report->district_id !== $church->district_id ||
                $report->sector_id !== $church->sector_id) {

                $newLocation = [
                    'region_id' => $church->region_id,
                    'district_id' => $church->district_id,
                    'sector_id' => $church->sector_id,
                ];
                $shouldUpdate = true;
            }
        } else {
            // Reporte SIN iglesia
            // Buscar iglesia activa desde pastor_ministries
            $iglesiaAsignada = PastorMinistry::where('pastor_id', $pastor->id)
                ->where('active', true)
                ->with('church')
                ->first()?->church;

            if ($iglesiaAsignada) {
                $newLocation = [
                    'church_id'   => $iglesiaAsignada->id,
                    'region_id'   => $iglesiaAsignada->region_id,
                    'district_id' => $iglesiaAsignada->district_id,
                    'sector_id'   => $iglesiaAsignada->sector_id,
                ];
                $shouldUpdate = true;
            } else {
                // Si no hay iglesia, usar ubicación del pastor
                if ($report->region_id !== $pastor->region_id ||
                    $report->district_id !== $pastor->district_id ||
                    $report->sector_id !== $pastor->sector_id) {

                    $newLocation = [
                        'region_id' => $pastor->region_id,
                        'district_id' => $pastor->district_id,
                        'sector_id' => $pastor->sector_id,
                    ];
                    $shouldUpdate = true;
                }
            }
        }

        if ($shouldUpdate) {
            $report->update($newLocation);

            \Log::info("Auto-updated offering_report during PDF generation", [
                'report_id' => $report->id,
                'updated_fields' => $newLocation,
                'reason' => $church ? 'church_location' : ($iglesiaAsignada ? 'pastor_ministry' : 'pastor_location')
            ]);
        }
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

    /**
     * 🔧 CORREGIDO: Ahora usa reportIds en lugar de churchIds
     * para incluir reportes SIN iglesia
     */
    private function resumenPorCategoria(Collection $reportIds, string $month): array
    {
        return DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->whereIn('offering_reports.id', $reportIds)
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