<?php

namespace App\Services;

use App\Models\{District, Sector, OfferingReport, OfferingCategory, OfferingDistribution, ExchangeRate};
use App\Services\ExchangeRateService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RegionalOfferingsReportService
{
    private function obtenerSectores(int $regionId): Collection
    {
        $districts = DB::table('districts')
            ->where('region_id', $regionId)
            ->pluck('id');

        return DB::table('sectors')
            ->whereIn('district_id', $districts)
            ->orderBy('name')
            ->get(['id', 'name', 'district_id']);
    }

    private function obtenerTasasPorSector(Collection $sectores, string $month): array
    {
        return app(ExchangeRateService::class)->tasasPorSector($sectores, $month);
    }

    public function generate(int $regionId, string $month, float $usdRate, float $copRate): string
    {
        $region = \App\Models\Region::find($regionId);

        $regionNombre = $region?->name ?? 'Región no encontrada';

        $categoriaIds = [1, 2, 5];

        $distritos = District::where('region_id', $regionId)->with('sectors')->get();
        
        $sectores = $this->obtenerSectores($regionId);

        $tasasPorSector = $this->obtenerTasasPorSector($sectores, $month);

        $distritosConSectores = [];

        foreach ($distritos as $distrito) {
            foreach ($distrito->sectors as $sector) {
                $ofrendas = DB::table('offering_items')
                    ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
                    ->where('offering_reports.month', $month)
                    ->where('offering_reports.status', 'aprobado')
                    ->where('offering_reports.sector_id', $sector->id)
                    ->whereIn('offering_items.offering_category_id', $categoriaIds)
                    ->select(
                        'offering_items.offering_category_id',
                        DB::raw('SUM(offering_items.subtotal_bs) as total_bs')
                    )
                    ->groupBy('offering_items.offering_category_id')
                    ->get();

                if ($ofrendas->isEmpty()) continue;

                $tasa = $tasasPorSector[$sector->id] ?? ['usd_rate' => null, 'cop_rate' => null];

                $deducciones = [];
                foreach ($ofrendas as $item) {
                    $porcentaje = OfferingDistribution::with('targetTreasury')
                        ->where('offering_category_id', $item->offering_category_id)
                        ->get()
                        ->filter(fn ($d) => strtolower($d->targetTreasury->level ?? '') === 'regional')
                        ->first()
                        ?->percentage ?? 0;

                    $deducidoBs = $item->total_bs * ($porcentaje / 100);
                    $deducidoUsd = $tasa['usd_rate'] ? $deducidoBs / $tasa['usd_rate'] : 0;
                    $deducidoCop = $tasa['cop_rate'] ? $deducidoBs * $tasa['cop_rate'] : 0;

                    $nombre = OfferingCategory::find($item->offering_category_id)?->name ?? '—';

                    $deducciones[] = [
                        'categoria_id' => $item->offering_category_id,
                        'categoria_nombre' => $nombre,
                        'monto_bs' => $deducidoBs,
                        'monto_usd' => $deducidoUsd,
                        'monto_cop' => $deducidoCop,
                    ];
                }

                $distritosConSectores[$distrito->name][$sector->id] = [
                    'sector_nombre' => $sector->name,
                    'deducciones' => $deducciones,
                ];
            }
        }

        $sectoresResumen = $this->generarResumenPorSector($regionId, $month);

        // ✅ Ahora sí se pasa el array de tasas para mostrarlas en el Blade
        return Pdf::loadView('pdfs.regional-offerings-report', [
            'month' => $month,
            'usdRate' => $usdRate,
            'copRate' => $copRate,
            'sectoresResumen' => $sectoresResumen,
            'distritosConSectores' => $distritosConSectores,
            'sectoresConTasas' => $tasasPorSector,
            'regionNombre' => $regionNombre,
        ])->output();
    }


    private function generarResumenPorSector(int $regionId, string $month): Collection
    {
        $categoriaIds = [1, 2, 5];

        $sectores = Sector::with('district')
            ->whereHas('district', fn ($q) => $q->where('region_id', $regionId))
            ->get();

        $totales = DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->where('offering_reports.month', $month)
            ->where('offering_reports.status', 'aprobado')
            ->whereIn('offering_items.offering_category_id', $categoriaIds)
            ->select(
                'offering_reports.sector_id',
                'offering_items.offering_category_id',
                DB::raw('SUM(offering_items.subtotal_bs) as total_bs')
            )
            ->groupBy('offering_reports.sector_id', 'offering_items.offering_category_id')
            ->get()
            ->groupBy('sector_id');

        return $sectores->sortBy('name')->map(function ($sector) use ($totales, $categoriaIds) {
            $items = $totales[$sector->id] ?? collect();
            $categorias = collect($categoriaIds)->mapWithKeys(fn ($id) => [$id => 0])->toArray();
            foreach ($items as $item) {
                $categorias[$item->offering_category_id] = $item->total_bs;
            }

            return [
                'sector' => $sector->name,
                'distrito' => $sector->district->name ?? '—',
                'categorias' => $categorias,
            ];
        })->values();
    }
}