<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DistrictOfferingsReportService;
use Illuminate\Support\Facades\Response;

class DistrictOfferingsReportController extends Controller
{
    public function generatePdf(Request $request, DistrictOfferingsReportService $service)
    {
        $month = $request->get('month');
        $districtId = $request->get('district');
        $usdRate = floatval($request->get('usd_rate'));
        $copRate = floatval($request->get('cop_rate'));

        $pdf = $service->generate($districtId, $month, $usdRate, $copRate);

        return response()->streamDownload(
            fn () => print($pdf),
            "reporte-distrito-{$month}.pdf",
            ['Content-Type' => 'application/pdf']
        );
        
    }
}