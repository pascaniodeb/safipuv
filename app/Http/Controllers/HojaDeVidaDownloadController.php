<?php

namespace App\Http\Controllers;

use App\Models\Pastor;
use App\Services\HojaDeVidaService;
use Illuminate\Http\Request;

class HojaDeVidaDownloadController extends Controller
{
    public function download(Pastor $pastor)
    {
        $pdfPath = app(HojaDeVidaService::class)->fillHojaDeVida($pastor);

        if (! file_exists($pdfPath)) {
            abort(404, 'No se encontró la Hoja de Vida.');
        }

        $filename = "hoja_de_vida_{$pastor->number_cedula}.pdf";

        // Retorna un BinaryFileResponse
        $response = response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);

        // Ajustar la cabecera para que sea inline
        // => "inline; filename=\"hoja_de_vida_xxx.pdf\""
        $disposition = "inline; filename=\"{$filename}\"";
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

}