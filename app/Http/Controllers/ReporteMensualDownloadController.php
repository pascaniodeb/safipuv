<?php

namespace App\Http\Controllers;

use App\Models\Pastor;
use App\Services\ReporteMensualOfrendasService;
use Illuminate\Http\Request;

class ReporteMensualDownloadController extends Controller
{
    public function download(Pastor $pastor)
    {
        $pdfPath = app(ReporteMensualOfrendasService::class)->fillReporteMensualOfrendas($pastor);

        if (! file_exists($pdfPath)) {
            abort(404, 'No se encontró el Reporte Mensual.');
        }

        $filename = "reporte_mensual_{$pastor->number_cedula}.pdf";

        // Retorna un BinaryFileResponse
        $response = response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);

        // Ajustar la cabecera para que sea inline
        // => "inline; filename=\"nombramiento_xxx.pdf\""
        $disposition = "inline; filename=\"{$filename}\"";
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

}