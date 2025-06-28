<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TopChurchesOfferingsReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class TopChurchesReportController extends Controller
{
    /**
     * Exporta el reporte como PDF o Excel
     */
    public function export(Request $request): Response
    {
        $request->validate([
            'categoria'  => 'required|string',
            'periodo'    => 'required|string|in:mes,trimestre,semestre,anual',
            'referencia' => 'nullable|date_format:Y-m',
            'formato'    => 'required|string|in:pdf,xlsx', // ✅ Validamos pdf o xlsx
        ]);

        $categoria  = $request->input('categoria');
        $periodo    = $request->input('periodo');
        $referencia = $request->input('referencia');
        $formato    = $request->input('formato');

        $servicio = new TopChurchesOfferingsReportService();
        $datos = $servicio->obtenerTopIglesias($periodo, $categoria, $referencia);

        $titulo = "Top 200 Iglesias - $categoria";
        $fechaLabel = $referencia ?? now()->format('Y-m');
        $nombreArchivo = Str::slug("200-iglesias-mayor-$categoria-$fechaLabel") . ".$formato";

        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('pdfs.top-200-churches', [
                'datos'      => $datos,
                'categoria'  => $categoria,
                'periodo'    => $periodo,
                'referencia' => $referencia,
                'titulo'     => $titulo,
            ]);

            return response()->streamDownload(
                fn () => print($pdf->output()),
                $nombreArchivo,
                ['Content-Type' => 'application/pdf']
            );
        }

        if ($formato === 'xlsx') {
            return Excel::download(
                new \App\Exports\TopChurchesExport(
                    $categoria,
                    $periodo,
                    $referencia,
                    $datos->toArray()
                ),
                $nombreArchivo
            );
        }

        abort(400, 'Formato no válido.');
    }
}