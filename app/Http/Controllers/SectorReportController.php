<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SectorOfferingsReportService;
use Symfony\Component\HttpFoundation\Response;

class SectorReportController extends Controller
{
    /**
     * Genera y descarga el PDF del reporte de Tesorería Sectorial.
     *
     * @param  int    $sectorId
     * @param  string $month     Formato: 'YYYY-MM'
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportPdf(int $sectorId, string $month, Request $request): Response
    {
        $user = Auth::user();

        if (
            ! $user->hasAnyRole(['Presbítero Sectorial', 'Tesorero Sectorial']) &&
            $user->sector_id !== $sectorId
        ) {
            abort(403, 'No tienes permiso para generar este reporte.');
        }

        // ✅ Capturar tasas desde la URL o fallback a 0
        $usdRate = (float) $request->input('usd_rate', 0);
        $copRate = (float) $request->input('cop_rate', 0);

        if ($usdRate <= 0 || $copRate <= 0) {
            abort(400, 'Las tasas de cambio son obligatorias y deben ser mayores a cero.');
        }

        // Generar el PDF con tasas
        $pdfBinary = app(SectorOfferingsReportService::class)
            ->generate($sectorId, $month, $usdRate, $copRate);

        $nombre = "reporte-sector-{$sectorId}-{$month}.pdf";

        return response()->streamDownload(
            fn () => print($pdfBinary),
            $nombre,
            ['Content-Type' => 'application/pdf']
        );
    }

}