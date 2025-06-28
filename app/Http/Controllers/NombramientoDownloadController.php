<?php

namespace App\Http\Controllers;

use App\Models\Pastor;
use App\Services\NombramientoService;
use Illuminate\Http\Request;

class NombramientoDownloadController extends Controller
{
    public function download(Pastor $pastor)
    {
        $pdfPath = app(NombramientoService::class)->fillNombramiento($pastor);

        if (! file_exists($pdfPath)) {
            abort(404, 'No se encontró el Nombramiento Pastoral.');
        }

        $filename = "nombramiento_{$pastor->number_cedula}.pdf";

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