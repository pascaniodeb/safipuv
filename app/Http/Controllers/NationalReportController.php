<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NationalOfferingsReportService;
use Symfony\Component\HttpFoundation\Response;

class NationalReportController extends Controller
{
    /**
     * Genera y descarga el PDF del reporte de Tesorería Nacional.
     *
     * @param  Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportPdf(Request $request): Response
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['Tesorero Nacional', 'Contralor Nacional'])) {
            abort(403, 'No tienes permiso para generar este reporte.');
        }

        $month = $request->input('month');

        if (! $month) {
            abort(400, 'Debe seleccionar el mes.');
        }

        $usdRate = (float) $request->input('usd_rate', 0);

        // ✅ Solo se valida tasa USD
        if ($usdRate <= 0) {
            abort(400, 'La tasa de cambio USD es obligatoria y debe ser mayor a cero.');
        }

        $pdfBinary = app(NationalOfferingsReportService::class)
            ->generate($month, $usdRate); // ✅ Solo se pasa usdRate

        $nombre = "reporte-nacional-{$month}.pdf";

        return response()->streamDownload(
            fn () => print($pdfBinary),
            $nombre,
            ['Content-Type' => 'application/pdf']
        );
    }
}