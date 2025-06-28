<?php

namespace App\Services;

use App\Models\Pastor;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use NumberFormatter;

class NombramientoService
{

    public function convertirNumeroALetras($numero): string
    {
        $formatter = new \NumberFormatter("es", \NumberFormatter::SPELLOUT);
        return strtoupper($formatter->format($numero));
    }

    private function obtenerNombreReducido(string $nombreCompleto, string $apellidoCompleto): string
    {
        $nombres = explode(' ', trim($nombreCompleto));
        $apellidos = explode(' ', trim($apellidoCompleto));

        $primerNombre = ucfirst(strtoupper($nombres[0] ?? ''));
        $inicialSegundoNombre = isset($nombres[1]) ? strtoupper(substr($nombres[1], 0, 1)) . '.' : '';

        $primerApellido = ucfirst(strtoupper($apellidos[0] ?? ''));
        $inicialSegundoApellido = isset($apellidos[1]) ? strtoupper(substr($apellidos[1], 0, 1)) . '.' : '';

        return "{$primerNombre} {$inicialSegundoNombre} {$primerApellido} {$inicialSegundoApellido}";
    }

    
    /**
     * Genera el Nombramiento Pastoral en un PDF de una página,
     * usando un PDF de plantilla y rellenando:
     *  - Datos del Pastor (pastor)
     *
     * @param  Pastor  $pastor
     * @return string  Ruta absoluta del PDF generado
     */
    public function fillNombramiento(Pastor $pastor): string
    {
        
        try {
            // 1) Ruta de la plantilla (ajusta según tu ubicación)
            $templatePath = storage_path('app/templates/nombramiento.pdf');

            // 2) Ruta de salida
            $outputPath = storage_path("app/public/documentos/nombramiento_{$pastor->number_cedula}.pdf");

            // 3) Obtener datos de la tabla pastor_ministries.
            $ministry = $pastor->pastorMinistry; // O pastorMinistries()->first() si es hasMany

            // 4) Preparar la data combinada
            $data = $this->prepareData($pastor, $ministry);

            $diaNum    = (int) now()->format('d');           // 29
            $mesNombre = strtoupper(now()->translatedFormat('F')); // SEPTIEMBRE
            $anioNum   = (int) now()->format('Y');           // 2025
            
            $diaLetra  = $this->convertirNumeroALetras($diaNum);   // VEINTINUEVE
            $anioLetra = $this->convertirNumeroALetras($anioNum);  // DOS MIL VEINTICINCO

            // 5) Crear instancia FPDI
            $pdf = new Fpdi();

            // 6) Añadir página (A4 vertical como ejemplo)
            $pdf->AddPage('P', 'Letter');

            // 7) Importar la plantilla (página 1)
            $pageCount = $pdf->setSourceFile($templatePath);
            $tplIdx    = $pdf->importPage(1);

            // 8) “Pegar” la plantilla en la página nueva
            $pdf->useTemplate($tplIdx, 0, 0, 216);

            // 9) Ajustar fuente, color, etc. (puedes cambiar la fuente si la tienes disponible)
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(0, 0, 0);

            // 10) Escribir cada campo en su posición (x,y) en mm
            // --- DATOS PERSONALES ---
            // (1) Nombre completo
            $pdf->SetXY(89.00, 86.00); // Ej: (50, 100)
            $pdf->Write(8, utf8_decode($data['nombre_completo']));

            // (2) Nacionalidad
            $pdf->SetXY(56.00, 91.00);
            $pdf->Write(8, $data['nacionalidad']);

            // (3) Estado civil
            $pdf->SetXY(140.00, 91.00);
            $pdf->Write(8, $data['estado_civil']);

            // (4) Inicial de nacionalidad
            if (strtoupper($data['nacionalidad']) === 'VENEZOLANO') {
                // Muestra la "V" en la posición correspondiente
                $pdf->SetXY(68.00, 96.50);
                $pdf->Write(8, 'V');
            } else {
                // Muestra la "E" para cualquier otra nacionalidad
                $pdf->SetXY(68.00, 96.50);
                $pdf->Write(8, 'E');
            }

            // (5) Cédula
            $pdf->SetXY(77.00, 96.50);
            $pdf->Write(8, number_format((int) $data['cedula'], 0, '', '.'));

            // (7) Nombre de la iglesia
            $pdf->SetXY(121.00, 102.00);
            $pdf->Write(8, utf8_decode($data['nombre_iglesia']));

            // (6) Dirección
            //$direccion = utf8_decode($data['direccion']);

            // 1. Cortar sin romper palabras en 2 líneas
            $direccionCompleta = implode(', ', array_filter([
                'Estado ' . ($data['estado_iglesia'] ?? ''),
                'Ciudad ' . ($data['ciudad_iglesia'] ?? ''),
                'Municipio ' . ($data['municipio_iglesia'] ?? ''),
                'Parroquia ' . ($data['parroquia_iglesia'] ?? ''),
                $data['direccion_iglesia'] ?? '',
            ]));
            
            $direccion = utf8_decode($direccionCompleta);
            $palabras = explode(' ', $direccion);
            
            // Establece el ancho máximo de línea en mm (ajusta según la plantilla)
            $maxWidth = 160; // puedes ajustarlo a 180 si tienes más espacio
            $linea1 = '';
            $linea2 = '';
            
            // Inicializamos con la misma fuente y tamaño del PDF
            $pdf->SetFont('Arial', '', 10);
            
            $currentLine = '';
            foreach ($palabras as $palabra) {
                $tentativa = ($currentLine === '') ? $palabra : $currentLine . ' ' . $palabra;
                if ($pdf->GetStringWidth($tentativa) <= $maxWidth) {
                    $currentLine = $tentativa;
                } else {
                    if ($linea1 === '') {
                        $linea1 = $currentLine;
                        $currentLine = $palabra;
                    } else {
                        $linea2 = $currentLine;
                        $currentLine = $palabra;
                        break; // Solo dos líneas, así que salimos
                    }
                }
            }
            // Asignar lo que quede
            if ($linea1 === '') {
                $linea1 = $currentLine;
            } elseif ($linea2 === '') {
                $linea2 = $currentLine;
            }
            
            // Escribir en el PDF
            $pdf->SetXY(23.00, 107.50); // Línea 1
            $pdf->Write(8, $linea1);
            
            $pdf->SetXY(23.00, 114.00); // Línea 2
            $pdf->Write(8, $linea2);
            
                        
            
            
            
            

            // 2. Rellenar la segunda línea con asteriscos si es más corta de 100
            //$linea2 = str_pad($linea2, 110, '*');

            // 3. Escribir en el PDF
            //$pdf->SetXY(121.00, 101.50); // Primera línea
            //$pdf->Write(8, $linea1);

            //$pdf->SetXY(23.00, 107.00); // Segunda línea (ajusta Y si lo necesitas)
            //$pdf->Write(8, $linea2);

            // (7) Nombre completo
            $pdf->SetXY(102.00, 139.00); // Ej: (50, 100)
            $pdf->Write(8, utf8_decode($data['nombre_completo']));

            // (8) Día en letras
            //$pdf->SetXY(23.00, 192.00); // Ajusta según tu plantilla
            //$pdf->Write(8, "{$diaLetra}");

            // (9) Día en números
            //$pdf->SetXY(77.00, 192.00); // Ajusta según tu plantilla
            //$pdf->Write(8, "{$diaNum}");

            // (10) Mes en mayúsculas
            //$pdf->SetXY(127.00, 192.00); // Ajusta según tu plantilla
            //$pdf->Write(8, $mesNombre);

            // (11) Año en letras
            //$pdf->SetXY(23.00, 197.00); // Ajusta según tu plantilla
            //$pdf->Write(8, "{$anioLetra}");

            // (12) Año en numero
            //$pdf->SetXY(87.00, 197.00); // Ajusta según tu plantilla
            //$pdf->Write(8, "{$anioNum}");

            // (13) Primer Nombre e Inicial del segundo, Primer Apellido e Inicial del segundo
            $nombreReducido = $this->obtenerNombreReducido($pastor->name, $pastor->lastname);
            $pdf->SetXY(92.00, 241.00);
            $pdf->Write(8, utf8_decode($nombreReducido));

            // --- DATOS MINISTERIALES ---
            // Código Pastoral
            $pdf->SetXY(154.00, 251.00);
            $pdf->Write(8, $data['codigo_pastoral']);

            // Guardar PDF
            $pdf->Output('F', $outputPath);

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('Error generando Nombramiento Pastoral', [
                'pastor_id' => $pastor->id,
                'exception' => $e->getMessage(),
            ]);

            throw $e; // o retornar null si prefieres
        }
    }

    /**
     * Combina la data necesaria para el Nombramiento Pastoral: personal, ministerial.
     */
    protected function prepareData(Pastor $pastor, $ministry): array
    {
        $church = $ministry->church;

        return [
            'nombre_completo'      => $pastor->name . ' ' . $pastor->lastname,
            'nacionalidad'         => $pastor->nationality->name ?? '',
            'estado_civil'         => $pastor->maritalStatus->name ?? '',
            'cedula'               => $pastor->number_cedula,
            'codigo_pastoral'      => $ministry->code_pastor,

            // Datos de la iglesia
            'nombre_iglesia'       => $church->name ?? '',
            'estado_iglesia'       => $church->state->name ?? '',
            'ciudad_iglesia'       => $church->city->name ?? '',
            'municipio_iglesia'    => $church->municipality->name ?? '',
            'parroquia_iglesia'    => $church->parish->name ?? '',
            'direccion_iglesia'    => $church->address ?? '',
        ];
    }

}