<?php

namespace App\Services;

use App\Models\OfferingReport;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;
use NumberFormatter;
use Carbon\Carbon;

class ReporteMensualOfrendasService
{
    /**
     * Genera el Reporte Mensual de Ofrendas en un PDF y retorna la ruta absoluta del archivo.
     */
    public function fillReporteMensual(OfferingReport $offeringReport): string
    {
        // Función local para formatear teléfonos venezolanos.
        function formatVenezuelanPhone($rawPhone): string
        {
            $digits = preg_replace('/\D/', '', $rawPhone);
            if (strlen($digits) === 10) {
                $digits = '0' . $digits;
            }
            if (strlen($digits) === 11) {
                $area   = substr($digits, 0, 4);
                $middle = substr($digits, 4, 3);
                $last   = substr($digits, 7);
                return $area . '-' . $middle . '.' . $last;
            }
            return $rawPhone;
        }

        try {
            // 1) Plantilla base (ajusta ruta a tu conveniencia)
            $templatePath = storage_path('app/templates/reporte_mensual.pdf');

            // 2) Ruta de salida
            $outputPath = storage_path(
                "app/public/documentos/reporte_mensual_{$offeringReport->id}.pdf"
            );

            // 3) Preparar la data a inyectar
            $data = $this->prepareData($offeringReport);

            // 4) Crear instancia FPDI y añadir página
            $pdf = new Fpdi();
            $pdf->AddPage('P', 'Letter');

            // 5) Cargar la plantilla (página 1)
            $pageCount = $pdf->setSourceFile($templatePath);
            $tplIdx    = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx, 0, 0, 216);

            // 6) Ajustar fuente y color
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(0, 0, 0);

            // -------------------------------------------------------------------
            // A. CAMPOS GENERALES (Pastor, Iglesia, Mes/Año Enviado)
            // -------------------------------------------------------------------

            // (1) MES ENVIADO
            $pdf->SetXY(147.00, 36.00);
            $pdf->Write(8, $data['mes_enviado']); // Ej. ENERO

            // (2) AÑO ENVIADO
            $pdf->SetXY(187.00, 36.00);
            $pdf->Write(8, $data['anio_enviado']); // Ej. 2025

            // (3) Nombre completo
            $pdf->SetXY(29.00, 44.00);
            $pdf->Write(8, utf8_decode($data['nombre_completo']));

            // (4) Cédula (formateada con puntos, p.ej. 8.765.432)
            $pdf->SetXY(127.00, 44.00);
            $pdf->Write(8, $data['cedula_formateada']);

            // (5) Código Pastoral
            $pdf->SetXY(176.00, 44.00);
            $pdf->Write(8, $data['codigo_pastoral']);

            // (6) Teléfono Celular
            $pdf->SetXY(23.00, 52.00);
            $pdf->Write(8, utf8_decode(formatVenezuelanPhone($data['telefono_mobile'])));

            // (7) Teléfono Residencial
            $pdf->SetXY(75.00, 52.00);
            $pdf->Write(8, utf8_decode(formatVenezuelanPhone($data['telefono_house'])));

            // (8) Email
            $pdf->SetXY(130.00, 51.50);
            $pdf->Write(8, $data['email']);

            // (9) Iglesia que pastorea
            $pdf->SetXY(20.00, 60.00);
            $pdf->Write(8, utf8_decode($data['iglesia_pastorea']));

            // (10) Código Iglesia
            $pdf->SetXY(175.00, 60.00);
            $pdf->Write(8, $data['codigo_iglesia']);

            // (11) Región
            $pdf->SetXY(22.00, 68.00);
            $pdf->Write(8, utf8_decode($data['region']));

            // (12) Distrito
            $pdf->SetXY(89.00, 68.00);
            $pdf->Write(8, utf8_decode($data['distrito']));

            // (13) Sector
            $pdf->SetXY(153.00, 68.00);
            $pdf->Write(8, utf8_decode($data['sector']));

            // (14) Dirección
            $pdf->SetXY(26.00, 76.00);
            $pdf->Write(8, utf8_decode($data['direccion']));

            // -------------------------------------------------------------------
            // B. OFRENDAS EN BOLÍVARES
            // -------------------------------------------------------------------
            $pdf->SetXY(107.00, 91.00);
            //       ancho   alto       texto         borde  salto-de-linea  alineación
            $pdf->Cell(20,    8,    $data['diezmo_bs'], 0,         0,          'R');

            $pdf->SetXY(107.00, 98.00); // (2) EL PODER DEL UNO
            $pdf->Cell(20, 8, $data['epdu_bs'], 0, 0, 'R');

            $pdf->SetXY(107.00, 105.00); // (3) SEDE NACIONAL
            $pdf->Cell(20, 8, $data['sede_nacional_bs'], 0, 0, 'R');

            // Texto: "CONVENCIÓN _______"
            $pdf->SetXY(52.00, 112.00);
            $pdf->Write(8, utf8_decode($data['convencion_label']));

            // Monto en Bs
            $pdf->SetXY(107.00, 112.00);
            // Con Cell() y alineación a la derecha
            $pdf->Cell(20, 8, $data['convencion_bs'], 0, 0, 'R');

            $pdf->SetXY(107.00, 119.00); // (7) ÚNICA SECTORIAL
            $pdf->Cell(20, 8, $data['unica_sectorial_bs'], 0, 0, 'R');

            $pdf->SetXY(107.00, 126.00); // (8) CAMPAMENTO DE Retiros
            $pdf->Cell(20, 8, $data['campamento_bs'], 0, 0, 'R');

            $pdf->SetXY(107.00, 133.00); // (9) Abisop
            $pdf->Cell(20, 8, $data['abisop_bs'], 0, 0, 'R');

            $pdf->SetXY(107.00, 140.00); // (10) Subtotal Bolívares
            $pdf->Cell(20, 8, $data['subtotal_bs'], 0, 0, 'R');

            // -------------------------------------------------------------------
            // C. OFRENDAS EN DÓLARES
            // -------------------------------------------------------------------
            $pdf->SetXY(146.00,  91.00); // (1) DIEZMOS
            $pdf->Cell(20, 8, $data['diezmo_usd'], 0, 0, 'R');

            $pdf->SetXY(146.00, 98.00); // (2) EL PODER DEL UNO
            $pdf->Cell(20, 8, $data['epdu_usd'], 0, 0, 'R');

            $pdf->SetXY(146.00, 105.00); // (3) SEDE NACIONAL
            $pdf->Cell(20, 8, $data['sede_nacional_usd'], 0, 0, 'R');

            // Convención
            $pdf->SetXY(146.00, 112.00);
            $pdf->Cell(20, 8, $data['convencion_usd'], 0, 0, 'R');

            $pdf->SetXY(146.00, 119.00); // (7) ÚNICA SECTORIAL
            $pdf->Cell(20, 8, $data['unica_sectorial_usd'], 0, 0, 'R');

            $pdf->SetXY(146.00, 126.00); // (8) CAMPAMENTO
            $pdf->Cell(20, 8, $data['campamento_usd'], 0, 0, 'R');

            $pdf->SetXY(146.00, 133.00); // (9) Abisop
            $pdf->Cell(20, 8, $data['abisop_usd'], 0, 0, 'R');

            $pdf->SetXY(146.00, 140.00); // (10) Subtotal
            $pdf->Cell(20, 8, $data['subtotal_usd'], 0, 0, 'R');

            // -------------------------------------------------------------------
            // D. OFRENDAS EN PESOS COLOMBIANOS
            // -------------------------------------------------------------------
            $pdf->SetXY(185.00,  91.00);
            $pdf->Cell(20, 8, $data['diezmo_cop'], 0, 0, 'R');

            $pdf->SetXY(185.00, 98.00);
            $pdf->Cell(20, 8, $data['epdu_cop'], 0, 0, 'R');

            $pdf->SetXY(185.00, 105.00);
            $pdf->Cell(20, 8, $data['sede_nacional_cop'], 0, 0, 'R');

            $pdf->SetXY(185.00, 112.00);
            $pdf->Cell(20, 8, $data['convencion_cop'], 0, 0, 'R');

            $pdf->SetXY(185.00, 119.00);
            $pdf->Cell(20, 8, $data['unica_sectorial_cop'], 0, 0, 'R');

            $pdf->SetXY(185.00, 126.00);
            $pdf->Cell(20, 8, $data['campamento_cop'], 0, 0, 'R');

            $pdf->SetXY(185.00, 133.00);
            $pdf->Cell(20, 8, $data['abisop_cop'], 0, 0, 'R');

            $pdf->SetXY(185.00, 140.00);
            $pdf->Cell(20, 8, $data['subtotal_cop'], 0, 0, 'R');

            // -------------------------------------------------------------------
            // E. TASAS DE CAMBIO Y TOTAL GENERAL
            // -------------------------------------------------------------------
            // Tasa USD
            $pdf->SetXY(51.00, 148.00);
            $pdf->Write(8, $data['usd_rate']); 

            // Tasa COP
            $pdf->SetXY(77.00, 148.00);
            $pdf->Write(8, $data['cop_rate']);

            // Total General en Bs
            $pdf->SetXY(185.00, 148.00);
            $pdf->Cell(20, 8, $data['grand_total_bs'], 0, 0, 'R');

            // 9) Guardar el PDF en disco
            $pdf->Output('F', $outputPath);

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('Error generando Reporte Mensual de Ofrendas', [
                'offering_report_id' => $offeringReport->id,
                'exception'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Construye el arreglo $data con todas las llaves que tu PDF requiere.
     */
    protected function prepareData(OfferingReport $report): array
    {
        // 1. Parseamos el mes (ejemplo: "2025-01") para extraer mes y año
        $mes   = $report->month ?? date('Y-m'); // Si no tiene, ponemos el actual
        // Usando Carbon para formatear
        $dt    = \Carbon\Carbon::parse($mes . '-01'); 
        $mesEn = mb_strtoupper($dt->monthName); // "ENERO", "FEBRERO", etc.
        $anio  = $dt->year;                    // 2025

        // 2. Datos del Pastor
        $pastor  = $report->pastor;
        $name    = trim(($pastor->name ?? '') . ' ' . ($pastor->lastname ?? ''));
        $cedulaF = $pastor && $pastor->number_cedula
            ? number_format((int) $pastor->number_cedula, 0, '', '.')
            : '';

        // 3. Datos Ministeriales (p.ej. desde pastorMinistry, si lo usas)
        $ministry       = $pastor->pastorMinistry ?? null; 
        $codigoPastoral = $ministry->code_pastor ?? '';
        $iglesia        = $report->church?->name 
                        ?? $ministry->church?->name 
                        ?? '';

        // 4. Agrupar items por categoría y función para obtener montos
        $itemsGrouped = $report->offeringItems->groupBy('offering_category_id');

        $getAmounts = function ($categoryId, $field) use ($itemsGrouped) {
            if (! $itemsGrouped->has($categoryId)) {
                return 0;
            }
            return $itemsGrouped[$categoryId]->sum($field);
        };

        // ---------------------------------------------------------------------------------
        // OFRENDAS “FIJAS” (Diezmos, EPDU, Sede Nacional, Única Sectorial, etc.)
        // ---------------------------------------------------------------------------------
        // DIEZMOS => ID=1
        $diezmo_bs  = $getAmounts(1, 'amount_bs');
        $diezmo_usd = $getAmounts(1, 'amount_usd');
        $diezmo_cop = $getAmounts(1, 'amount_cop');

        // EL PODER DEL UNO => ID=2
        $epdu_bs  = $getAmounts(2, 'amount_bs');
        $epdu_usd = $getAmounts(2, 'amount_usd');
        $epdu_cop = $getAmounts(2, 'amount_cop');

        // SEDE NACIONAL => ID=3
        $sedeNac_bs  = $getAmounts(3, 'amount_bs');
        $sedeNac_usd = $getAmounts(3, 'amount_usd');
        $sedeNac_cop = $getAmounts(3, 'amount_cop');

        // ÚNICA SECTORIAL => ID=7
        $unicaSector_bs  = $getAmounts(7, 'amount_bs');
        $unicaSector_usd = $getAmounts(7, 'amount_usd');
        $unicaSector_cop = $getAmounts(7, 'amount_cop');

        // CAMPAMENTO DE RETIROS => 8
        $camp_bs  = $getAmounts(8, 'amount_bs');
        $camp_usd = $getAmounts(8, 'amount_usd');
        $camp_cop = $getAmounts(8, 'amount_cop');

        // ABISOP => 9
        $abisop_bs  = $getAmounts(9, 'amount_bs');
        $abisop_usd = $getAmounts(9, 'amount_usd');
        $abisop_cop = $getAmounts(9, 'amount_cop');

        // ---------------------------------------------------------------------------------
        // CONVENCIÓN (SOLO UNA: Distrital=4, Regional=5, o Nacional=6)
        // ---------------------------------------------------------------------------------
        $convencionLabel = '';
        $convencionBs    = 0;
        $convencionUsd   = 0;
        $convencionCop   = 0;

        if ($itemsGrouped->has(4)) {            // Distrital
            $convencionLabel = 'DISTRITAL';
            $convencionBs    = $getAmounts(4, 'amount_bs');
            $convencionUsd   = $getAmounts(4, 'amount_usd');
            $convencionCop   = $getAmounts(4, 'amount_cop');
        } elseif ($itemsGrouped->has(5)) {      // Regional
            $convencionLabel = 'REGIONAL';
            $convencionBs    = $getAmounts(5, 'amount_bs');
            $convencionUsd   = $getAmounts(5, 'amount_usd');
            $convencionCop   = $getAmounts(5, 'amount_cop');
        } elseif ($itemsGrouped->has(6)) {      // Nacional
            $convencionLabel = 'NACIONAL';
            $convencionBs    = $getAmounts(6, 'amount_bs');
            $convencionUsd   = $getAmounts(6, 'amount_usd');
            $convencionCop   = $getAmounts(6, 'amount_cop');
        }

        // 5. Subtotales en cada moneda
        $subtotalBS  = $report->offeringItems->sum('amount_bs');
        $subtotalUSD = $report->offeringItems->sum('amount_usd');
        $subtotalCOP = $report->offeringItems->sum('amount_cop');

        // 6. Tasas y total general
        $usdRate      = $report->usd_rate; 
        $copRate      = $report->cop_rate;
        $grandTotalBs = $report->grand_total_bs;

        // 7. Retornamos todos los datos con formato apropiado
        return [
            // Mes / Año
            'mes_enviado'  => $mesEn,   // "ENERO"
            'anio_enviado' => $anio,    // "2025"

            // Pastor e Iglesia
            'nombre_completo'   => $name,
            'cedula_formateada' => $cedulaF,
            'cedula'            => $pastor->number_cedula ?? '',
            'codigo_pastoral'   => $codigoPastoral,
            'telefono_mobile'   => $pastor->phone_mobile ?? '',
            'telefono_house'    => $pastor->phone_house ?? '',
            'email'             => $pastor->email ?? '',
            'iglesia_pastorea'  => $iglesia,
            'codigo_iglesia'    => $ministry->code_church ?? '',
            'region'            => $report->region?->name ?? '',
            'distrito'          => $report->district?->name ?? '',
            'sector'            => $report->sector?->name ?? '',
            'direccion'         => $pastor->address ?? '',

            // MONTOS “FIJOS” EN Bs, USD, COP
            'diezmo_bs'             => number_format($diezmo_bs, 2, ',', '.'),
            'diezmo_usd'            => number_format($diezmo_usd, 2, ',', '.'),
            'diezmo_cop'            => number_format($diezmo_cop, 2, ',', '.'),

            'epdu_bs'               => number_format($epdu_bs, 2, ',', '.'),
            'epdu_usd'              => number_format($epdu_usd, 2, ',', '.'),
            'epdu_cop'              => number_format($epdu_cop, 2, ',', '.'),

            'sede_nacional_bs'      => number_format($sedeNac_bs, 2, ',', '.'),
            'sede_nacional_usd'     => number_format($sedeNac_usd, 2, ',', '.'),
            'sede_nacional_cop'     => number_format($sedeNac_cop, 2, ',', '.'),

            'unica_sectorial_bs'    => number_format($unicaSector_bs, 2, ',', '.'),
            'unica_sectorial_usd'   => number_format($unicaSector_usd, 2, ',', '.'),
            'unica_sectorial_cop'   => number_format($unicaSector_cop, 2, ',', '.'),

            'campamento_bs'         => number_format($camp_bs, 2, ',', '.'),
            'campamento_usd'        => number_format($camp_usd, 2, ',', '.'),
            'campamento_cop'        => number_format($camp_cop, 2, ',', '.'),

            'abisop_bs'             => number_format($abisop_bs, 2, ',', '.'),
            'abisop_usd'            => number_format($abisop_usd, 2, ',', '.'),
            'abisop_cop'            => number_format($abisop_cop, 2, ',', '.'),

            // CONVENCIÓN (solo 1)
            'convencion_label'      => $convencionLabel, // "DISTRITAL", "REGIONAL", "NACIONAL", o ""
            'convencion_bs'         => number_format($convencionBs, 2, ',', '.'),
            'convencion_usd'        => number_format($convencionUsd, 2, ',', '.'),
            'convencion_cop'        => number_format($convencionCop, 2, ',', '.'),

            // Subtotales
            'subtotal_bs'           => number_format($subtotalBS, 2, ',', '.'),
            'subtotal_usd'          => number_format($subtotalUSD, 2, ',', '.'),
            'subtotal_cop'          => number_format($subtotalCOP, 2, ',', '.'),

            // Tasas y total general
            'usd_rate'              => number_format($usdRate ?? 0, 2, ',', '.'),
            'cop_rate'              => number_format($copRate ?? 0, 2, ',', '.'),
            'grand_total_bs'        => number_format($grandTotalBs, 2, ',', '.'),
        ];
    }

}