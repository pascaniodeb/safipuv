<?php

namespace App\Services;

use App\Models\Sector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ExchangeRateService;

class NationalOfferingsReportService
{
    private const CATEGORIAS = [1, 2, 3, 6, 7, 9];

    private function obtenerSectores(): Collection
    {
        return Sector::with('district')->orderBy('name')->get();
    }

    private function generarResumenPorSector(Collection $sectores, string $month): array
    {
        $items = DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->where('offering_reports.month', $month)
            ->where('offering_reports.status', 'aprobado')
            ->whereIn('offering_items.offering_category_id', self::CATEGORIAS)
            ->select(
                'offering_reports.sector_id',
                'offering_items.offering_category_id',
                DB::raw('SUM(offering_items.subtotal_bs) as total_bs')
            )
            ->groupBy('offering_reports.sector_id', 'offering_items.offering_category_id')
            ->get()
            ->groupBy('sector_id');

        // ✅ Obtener tasas mediante el servicio compartido
        $tasas = app(ExchangeRateService::class)->tasasPorSector($sectores, $month);

        $totalesPorCategoria = [1 => 0, 2 => 0, 3 => 0, 6 => 0, 7 => 0, 9 => 0];
        $sectoresResumen = [];

        foreach ($sectores as $sector) {
            $categoriaMontos = collect(self::CATEGORIAS)->mapWithKeys(fn ($id) => [$id => 0])->toArray();
            $sectorItems = $items[$sector->id] ?? collect();

            foreach ($sectorItems as $item) {
                $categoriaMontos[$item->offering_category_id] = $item->total_bs;
                $totalesPorCategoria[$item->offering_category_id] += $item->total_bs;
            }

            $sectoresResumen[] = [
                'id'         => $sector->id,
                'sector'     => $sector->name,
                'usd_rate'   => $tasas[$sector->id]['usd_rate'] ?? null,
                'cop_rate'   => $tasas[$sector->id]['cop_rate'] ?? null,
                'categorias' => $categoriaMontos,
            ];
        }

        $cuadros = [
            'descuento_diezmos' => [
                ['label' => 'SECTORES', 'monto' => $totalesPorCategoria[1] * 0.25],
                ['label' => 'DISTRITOS', 'monto' => $totalesPorCategoria[1] * 0.075],
                ['label' => 'REGIONES', 'monto' => $totalesPorCategoria[1] * 0.135],
            ],
            'descuento_poder_uno' => [
                ['label' => 'SECTORES', 'monto' => $totalesPorCategoria[2] * 0.15],
                ['label' => 'DISTRITOS', 'monto' => $totalesPorCategoria[2] * 0.085],
                ['label' => 'REGIONES TESORERÍA REGIONAL', 'monto' => $totalesPorCategoria[2] * 0.0765],
                ['label' => 'REGIONES NÚCLEO DE ESTUDIO', 'monto' => $totalesPorCategoria[2] * 0.1033],
            ],
            'descuento_unica_sectorial' => [
                ['label' => 'SECTOR - TESORERÍA SECTORIAL', 'monto' => $totalesPorCategoria[7] * 0.5],
                ['label' => 'SECTOR - PASTORES', 'monto' => $totalesPorCategoria[7] * 0.5],
            ],
            'descuento_abisop' => [
                ['label' => 'SECTORES', 'monto' => $totalesPorCategoria[9]],
            ],
            'ingresos_nacionales' => [
                ['label' => 'DIEZMOS', 'monto' => $totalesPorCategoria[1] * 0.54],
                ['label' => 'EL PODER DEL UNO', 'monto' => $totalesPorCategoria[2] * 0.5852],
                ['label' => 'SEDE NACIONAL', 'monto' => $totalesPorCategoria[3]],
                ['label' => 'CONVENCIÓN NACIONAL', 'monto' => $totalesPorCategoria[6]],
            ],
        ];

        return [
            'sectoresResumen' => collect($sectoresResumen),
            'cuadros' => $cuadros,
        ];
    }

    public function generate(string $month, float $usdRate): string
    {
        $sectores = $this->obtenerSectores();
        $resumen = $this->generarResumenPorSector($sectores, $month);

        return Pdf::loadView('pdfs.national-offerings-report', [
            'month' => $month,
            'usdRate' => $usdRate, // ✅ ESTO ES LO QUE VA AL BLADE
            'sectoresResumen' => $resumen['sectoresResumen'],
            'cuadros' => $resumen['cuadros'],
        ])->output();
    }
}