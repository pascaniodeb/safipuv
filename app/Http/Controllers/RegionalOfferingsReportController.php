<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use App\Services\RegionalOfferingsReportService;

class RegionalOfferingsReportController extends Controller
{
    public function generatePdf(Request $request, RegionalOfferingsReportService $service)
    {
        $month = $request->get('month');
        $regionId = $request->get('region');
        $usdRate = floatval($request->get('usd_rate'));
        $copRate = floatval($request->get('cop_rate'));

        // ✅ Validación de permisos
        //$user = Auth::user();
        //if (
            //! $user->hasAnyRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional']) ||
            //$user->region_id !== $regionId
        //) {
            //abort(403, 'No tienes permiso para generar este reporte.');
        //}


        // ✅ Validación de tasas mínimas
        if ($usdRate <= 0 || $copRate <= 0) {
            abort(400, 'Las tasas de cambio son obligatorias y deben ser mayores a cero.');
        }

        $pdf = $service->generate($regionId, $month, $usdRate, $copRate);

        return response()->streamDownload(
            fn () => print($pdf),
            "reporte-region-{$month}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}