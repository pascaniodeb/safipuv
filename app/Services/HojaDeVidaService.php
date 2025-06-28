<?php

namespace App\Services;

use App\Models\Pastor;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class HojaDeVidaService
{
    /**
     * Genera un archivo de QR con datos básicos del Pastor.
     * Retorna la ruta absoluta del PNG generado.
     */
    private function generateQrForHojaDeVida(Pastor $pastor): string
    {
        // Asegurar que la carpeta 'hojadevida' exista
        \Storage::disk('public')->makeDirectory('hojadevida');

        // Ruta donde guardar el QR
        $qrCodePath = storage_path("app/public/hojadevida/{$pastor->number_cedula}_qr.png");

        // Si ya existe un QR anterior, lo eliminamos
        if (file_exists($qrCodePath)) {
            unlink($qrCodePath);
        }

        // Datos que quieres codificar
        $qrData = [
            'Apellidos' => $pastor->lastname,   // Ej: "Gómez"
            'Nombres'   => $pastor->name,       // Ej: "José Miguel"
            'Cédula'    => $pastor->number_cedula
        ];

        // Arma la cadena en UTF-8 sin escapado de caracteres
        $dataString = json_encode($qrData, JSON_UNESCAPED_UNICODE);

        // Generar QR con encoding('UTF-8')
        \QrCode::format('png')
            ->encoding('UTF-8')   // <-- para indicar a la librería que es UTF-8
            ->size(300)
            ->generate($dataString, $qrCodePath);

        return $qrCodePath;
    }


    
    private function calcularAniosMinisterio(Pastor $pastor): int
    {
        if (!$pastor->start_date_ministry) {
            // Si no tiene fecha, devolvemos 0 o el valor que prefieras
            return 0;
        }

        // Obtenemos la diferencia en años
        // Suponiendo que uses Carbon:
        // $start = Carbon::parse($pastor->start_date_ministry);
        // return $start->diffInYears(now());

        // Si ya estás usando "use Illuminate\Support\Carbon", quedaría:
        $start = \Carbon\Carbon::parse($pastor->start_date_ministry);
        return $start->diffInYears(\Carbon\Carbon::now());
    }


    /**
     * Genera la Hoja de Vida Pastoral en un PDF de una página,
     * usando un PDF de plantilla y rellenando:
     *  - Datos Personales (pastors)
     *  - Datos Familiares (families)
     *  - Datos Ministeriales (pastor_ministries)
     *
     * @param  Pastor  $pastor
     * @return string  Ruta absoluta del PDF generado
     */
    public function fillHojaDeVida(Pastor $pastor): string
    {
        /**
         * Convierte un número como '4127915392' en '0412-791.5392'.
         */
        function formatVenezuelanPhone($rawPhone): string
        {
            // 1. Solo dígitos
            $digits = preg_replace('/\D/', '', $rawPhone);

            // 2. Si es exactamente 10 dígitos (por ej. '4127915392'), agregamos '0' para hacerlo 11
            //    quedaría '04127915392'
            if (strlen($digits) === 10) {
                $digits = '0' . $digits; 
            }

            // 3. Ahora tenemos algo como '04127915392' (11 dígitos).
            //    Subdividimos: '0412' => '-', => '791' => '.', => '5392'
            if (strlen($digits) === 11) {
                $area  = substr($digits, 0, 4);  // '0412'
                $middle = substr($digits, 4, 3); // '791'
                $last   = substr($digits, 7);    // '5392'
                return $area . '-' . $middle . '.' . $last; 
            }

            // Si no coincide con 10 o 11 dígitos, retornamos como estaba, o ajusta a tu preferencia
            return $rawPhone; 
        }
        
        try {
            // 1) Ruta de la plantilla (ajusta según tu ubicación)
            $templatePath = storage_path('app/templates/hoja_de_vida.pdf');

            // 2) Ruta de salida
            $outputPath = storage_path("app/public/documentos/hoja_vida_{$pastor->number_cedula}.pdf");

            // 3) Obtener datos de la tabla families y pastor_ministries.
            //    Aquí asumo que un Pastor tiene 1 registro family y 1 pastorMinistry (ajusta según tu relación).
            $family = $pastor->family;           // O families()->first() si es hasMany
            $ministry = $pastor->pastorMinistry; // O pastorMinistries()->first() si es hasMany

            // 4) Preparar la data combinada
            $data = $this->prepareData($pastor, $family, $ministry);

            // 5) Crear instancia FPDI
            $pdf = new Fpdi();

            // 6) Añadir página (A4 vertical como ejemplo)
            $pdf->AddPage('P', 'Letter');

            // 7) Importar la plantilla (página 1)
            $pageCount = $pdf->setSourceFile($templatePath);
            $tplIdx    = $pdf->importPage(1);

            // 8) “Pegar” la plantilla en la página nueva
            $pdf->useTemplate($tplIdx, 0, 0, 216);

            // *** Insertar la foto del Pastor
            $photoPath = storage_path("app/public/{$pastor->photo_pastor}");
            if (!file_exists($photoPath) || empty($pastor->photo_pastor)) {
                $photoPath = public_path('images/default-photo.png');
            }
            $pdf->Image($photoPath, 168.5, 31.5, 35, 39.5);

            \Log::info('Verificando si existe esposa', [
                'existe' => isset($family),
                'relation_id' => $family?->relation_id,
                'foto' => $family?->photo_spouse,
            ]);

            $relationIdEsposa = 1; // ajusta si es otro ID

            $esposa = $pastor->families()->where('relation_id', $relationIdEsposa)->first();

            \Log::info('Verificando si existe esposa', [
                'existe' => $esposa ? true : false,
                'relation_id' => $esposa->relation_id ?? null,
                'foto' => $esposa->photo_spouse ?? null,
            ]);

            // *** Insertar la foto de la esposa
            if ($esposa && $esposa->photo_spouse) {
                $photoSpousePath = storage_path("app/public/{$esposa->photo_spouse}");
                if (!file_exists($photoSpousePath)) {
                    $photoSpousePath = public_path('images/default-photo.png');
                }
            } else {
                $photoSpousePath = public_path('images/default-photo.png');
            }
            
            $pdf->Image($photoSpousePath, 183.00, 140.00, 21.5, 23.0);
            
            
            

            

            
            

            


            // Generar el QR usando el método interno
            $qrCodePath = $this->generateQrForHojaDeVida($pastor);

            // Insertar la imagen en el PDF. Según tus coordenadas (168.5, 234.50) a (203.50, 267.50)
            // => ancho ~35mm y alto ~32.5mm
            $pdf->Image($qrCodePath, 168.5, 234.50, 35, 32.5);

            // 9) Ajustar fuente, color, etc. (puedes cambiar la fuente si la tienes disponible)
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(0, 0, 0);

            // 10) Escribir cada campo en su posición (x,y) en mm
            // --- DATOS PERSONALES ---
            // (1) Nombre completo
            $pdf->SetXY(30.00, 52.00); // Ej: (50, 100)
            $pdf->Write(8, utf8_decode($data['nombre_completo']));

            // (2) Nacionalidad
            $pdf->SetXY(32.00, 60.00);
            $pdf->Write(8, $data['nacionalidad']);

            // (3) Cédula
            $pdf->SetXY(88.00, 60.00);
            $pdf->Write(8, number_format((int) $data['cedula'], 0, '', '.'));

            // (4) Tipo de sangre
            $pdf->SetXY(140.00, 60.00);
            $pdf->Write(8, $data['tipo_sangre']);

            // (5) Fecha de nacimiento
            $pdf->SetXY(30.00, 68.00);
            $pdf->Write(8, $data['fecha_nacimiento']);

            // (6) Lugar de nacimiento
            $pdf->SetXY(81.50, 68.00);
            $pdf->Write(8, utf8_decode($data['lugar_nacimiento']));

            // (7) Nivel académico
            $pdf->SetXY(29.00, 76.00);
            $pdf->Write(8, $data['nivel_academico']);

            // (8) Carrera
            $pdf->SetXY(83.50, 76.00);
            $pdf->Write(8, utf8_decode($data['carrera']));

            // (9) Teléfono Celular
            $pdf->SetXY(175.25, 76.00);
            $pdf->Write(8, utf8_decode(formatVenezuelanPhone($data['telefono_mobile'])));

            // (10) Teléfono Residencial
            $pdf->SetXY(30.00, 84.00);
            $pdf->Write(8, utf8_decode(formatVenezuelanPhone($data['telefono_house'])));

            // (11) Email
            $pdf->SetXY(82.00, 84.00);
            $pdf->Write(8, $data['email']);

            // (12) Estado civil
            $pdf->SetXY(175.25, 84.00);
            $pdf->Write(8, $data['estado_civil']);

            // (13) Fecha de bautismo
            $pdf->SetXY(27.00, 92.00);
            $pdf->Write(8, $data['fecha_bautismo']);

            // (14) Quién bautizó
            $pdf->SetXY(74, 92.00);
            $pdf->Write(8, utf8_decode($data['quien_bautizo']));

            // (15) Fecha de inicio del ministerio
            $pdf->SetXY(178.00, 92.00);
            $pdf->Write(8, $data['fecha_ministerio']);

            // (15b) Años en el ministerio
            $pdf->SetXY(45.00, 100.00);
            $pdf->Write(8, $data['anios_ministerio']);

            // (16) Región
            $pdf->SetXY(70.50, 100.00);
            $pdf->Write(8, utf8_decode($data['region']));

            // (17) Distrito
            $pdf->SetXY(147.00, 100.00);
            $pdf->Write(8, utf8_decode($data['distrito']));

            // (18) Sector
            $pdf->SetXY(22.00, 108.00);
            $pdf->Write(8, utf8_decode($data['sector']));

            // (19) Estado
            $pdf->SetXY(88, 108.00);
            $pdf->Write(8, utf8_decode($data['estado']));

            // (20) Municipio
            $pdf->SetXY(156.00, 108.00);
            $pdf->Write(8, utf8_decode($data['municipio']));

            // (21) Tipo de vivienda
            $pdf->SetXY(25.00, 116.00);
            $pdf->Write(8, $data['tipo_vivienda']);

            // (22) Dirección
            $pdf->SetXY(77.00, 116.00);
            // Si la dirección es larga, podrías usar $pdf->MultiCell(...) para partir en varias líneas.
            $pdf->Write(8, utf8_decode($data['direccion']));

            // (23) Género
            $pdf->SetXY(26, 123.00);
            $genero = $data['genero']; // Por ejemplo, "Masculino" / "Femenino"
            switch ($genero) {
                case 'Masculino':
                    $genero = 'M';
                    break;
                case 'Femenino':
                    $genero = 'F';
                    break;
                default:
                    $genero = ''; 
            }
            $pdf->Write(8, utf8_decode($genero));


            // (24) Seguro social (Sí/No)
            if ($data['seguro_social'] === 'Sí') {
                // Mover cursor a la posición de “Sí”
                $pdf->SetXY(65.00, 124.00);
                $pdf->Write(8, 'X'); 
            } else {
                // Mover cursor a la posición de “No”
                $pdf->SetXY(78.00, 124.00);
                $pdf->Write(8, 'X');
            }

            // (25) Política de vivienda (Sí/No)
            if ($data['politica_vivienda'] === 'Sí') {
                // Mover cursor a la posición de “Sí”
                $pdf->SetXY(124.00, 124.00);
                $pdf->Write(8, 'X'); 
            } else {
                // Mover cursor a la posición de “No”
                $pdf->SetXY(136.00, 124.00);
                $pdf->Write(8, 'X');
            }

            // (26) Otro trabajo (Sí/No)
            if ($data['otro_trabajo'] === 'Sí') {
                // Mover cursor a la posición de “Sí”
                $pdf->SetXY(189.00, 124.00);
                $pdf->Write(8, 'X'); 
            } else {
                // Mover cursor a la posición de “No”
                $pdf->SetXY(201.00, 124.00);
                $pdf->Write(8, 'X');
            }

            // (27) Forma de trabajo
            $pdf->SetXY(28.00, 132.00);
            $pdf->Write(8, utf8_decode($data['forma_trabajo']));

            // (28) Otros estudios
            $pdf->SetXY(136.50, 132.00);
            $pdf->Write(8, utf8_decode($data['otros_estudios']));

            // --- DATOS FAMILIARES ---
            // (1) Nombre completo esposa
            $pdf->SetXY(50.00, 151.00);
            $pdf->Write(8, utf8_decode($data['nombre_completo_esposa']));

            // (2) Nacionalidad
            $pdf->SetXY(31.00, 159.00);
            $pdf->Write(8, $data['nacionalidad_esposa']);

            // (3) Cédula
            $pdf->SetXY(86.00, 159.00);
            $pdf->Write(8, number_format((int) $data['cedula_esposa'], 0, '', '.'));

            // (4) Tipo de sangre
            $pdf->SetXY(131.00, 159.00);
            $pdf->Write(8, $data['tipo_sangre_esposa']);

            // (5) Teléfono Celular
            $pdf->SetXY(152.00, 159.00);
            $pdf->Write(8, utf8_decode(formatVenezuelanPhone($data['telefono_mobile_esposa'])));

            // (6) Fecha de nacimiento
            $pdf->SetXY(29.00, 167.00);
            $pdf->Write(8, $data['fecha_nacimiento_esposa']);

            // (7) Lugar de nacimiento
            $pdf->SetXY(80.00, 167.00);
            $pdf->Write(8, utf8_decode($data['lugar_nacimiento_esposa']));

            // (8) Tipo de cargo
            $pdf->SetXY(175.00, 167.00);
            $pdf->Write(8, $data['tipo_cargo_esposa']);

            // (9) Cargo actual
            $pdf->SetXY(20.00, 175.00);
            $pdf->Write(8, utf8_decode($data['cargo_actual_esposa']));

            // (10) Email
            $pdf->SetXY(130.00, 175.00);
            $pdf->Write(8, $data['email_esposa']);

            // (11) Nivel académico
            $pdf->SetXY(28.00, 183.00);
            $pdf->Write(8, $data['nivel_academico_esposa']);

            // (12) Carrera
            $pdf->SetXY(82.00, 183.00);
            $pdf->Write(8, utf8_decode($data['carrera_esposa']));

            // (12) Cuantos hijos varones
            $pdf->SetXY(181.00, 183.50);
            $pdf->Write(8, $data['cantidad_hijos_varones']);

            // (13) Cuantos hijas mujeres
            $pdf->SetXY(200.00, 183.50);
            $pdf->Write(8, $data['cantidad_hijas_mujeres']);

            // (14) Nombres de los hijos
            $pdf->SetXY(28.00, 191.00); // Ajusta según tu plantilla
            $pdf->Write(8, $data['nombres_hijos']);




            // --- DATOS MINISTERIALES ---
            // (1) Tipo de pastor
            $pdf->SetXY(22.00, 210.00);
            $pdf->Write(8, $data['tipo_pastor']);

            // (2) Nivel pastoral
            $pdf->SetXY(63.00, 210.00);
            $pdf->Write(8, $data['pastor_nivel']);

            // (3) Grado licencia
            $pdf->SetXY(108.00, 210.00);
            $pdf->Write(8, utf8_decode($data['grado_licencia']));

            // (4) Nombramiento Pastoral (Sí/No)
            if ($data['nombramiento_pastoral'] === 'Sí') {
                $pdf->SetXY(190.00, 210.00);
                $pdf->Write(8, 'X'); 
            } else {
                $pdf->SetXY(201.50, 210.00);
                $pdf->Write(8, 'X');
            }

            // (5) Código Pastoral
            $pdf->SetXY(24.00, 218.00);
            // Cambiamos 'grado_licencia' a 'codigo_pastoral'
            $pdf->Write(8, $data['codigo_pastoral']);

            // (6) Iglesia que pastorea
            $pdf->SetXY(72.00, 218.00);
            $pdf->Write(8, utf8_decode($data['iglesia_pastorea']));

            // (7) Código Iglesia
            $pdf->SetXY(175.00, 218.00);
            $pdf->Write(8, $data['codigo_iglesia']);

            // (8) Tipo de cargo
            $pdf->SetXY(22.00, 226.00);
            $pdf->Write(8, $data['tipo_cargo']);

            // (9) Cargo actual
            $pdf->SetXY(65.00, 226.00);
            $pdf->Write(8, utf8_decode($data['cargo_actual']));

            // (10) Egresado IBLC (Sí/No)
            if ($data['egresado_iblc'] === 'Sí') {
                $pdf->SetXY(35.00, 234.50);
                $pdf->Write(8, 'X'); 
            } else {
                $pdf->SetXY(46.00, 234.50);
                $pdf->Write(8, 'X');
            }

            // (11) Tipo de curso
            $pdf->SetXY(65.00, 234.00);
            $pdf->Write(8, $data['tipo_curso']);

            // (12) Año de promoción
            $pdf->SetXY(114.00, 234.00);
            $pdf->Write(8, $data['ano_promocion']);

            // (13) Numero de promoción
            $pdf->SetXY(148.00, 234.00);
            $pdf->Write(8, $data['numero_promocion']);

            // (14) Cancela ABISOP (Sí/No)
            if ($data['cancela_abisop'] === 'Sí') {
                $pdf->SetXY(28.00, 242.50);
                $pdf->Write(8, 'X'); 
            } else {
                $pdf->SetXY(39.50, 242.50);
                $pdf->Write(8, 'X');
            }


            // 11) Guardar PDF
            $pdf->Output('F', $outputPath);

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('Error generando Hoja de Vida Pastoral', [
                'pastor_id' => $pastor->id,
                'exception' => $e->getMessage(),
            ]);

            throw $e; // o retornar null si prefieres
        }
    }

    /**
     * Combina la data necesaria para la Hoja de Vida: personal, familiar, ministerial.
     */
    protected function prepareData(Pastor $pastor, $family, $ministry): array
    {
        // DATOS PERSONALES (pastors)
        $datosPersonales = [
            // (1) Nombre completo
            'nombre_completo'   => $pastor->name . ' ' . $pastor->lastname,
        
            // (2) Nacionalidad
            'nacionalidad'      => $pastor->nationality->name ?? '',
        
            // (3) Cédula
            'cedula'            => $pastor->number_cedula,
        
            // (4) Tipo de sangre
            'tipo_sangre'       => $pastor->bloodType->name ?? '',
        
            // (5) Fecha de nacimiento
            'fecha_nacimiento'  => optional($pastor->birthdate)->format('d/m/Y'),
        
            // (6) Lugar de nacimiento
            'lugar_nacimiento'  => $pastor->birthplace ?? '',
        
            // (7) Nivel académico
            'nivel_academico'   => $pastor->academicLevel->name ?? '',
        
            // (8) Carrera
            'carrera'           => $pastor->career ?? '',
        
            // (9) Teléfono Celular
            'telefono_mobile'   => $pastor->phone_mobile ?? '',
        
            // (10) Teléfono Residencial
            'telefono_house'    => $pastor->phone_house ?? '',
        
            // (11) Email
            'email'             => $pastor->email,
        
            // (12) Estado civil
            'estado_civil'      => $pastor->maritalStatus->name ?? '',
        
            // (13) Fecha de bautismo
            'fecha_bautismo'    => optional($pastor->baptism_date)->format('d/m/Y'),
        
            // (14) Quién bautizó
            'quien_bautizo'     => $pastor->who_baptized ?? '',
        
            // (15) Fecha de inicio del ministerio
            'fecha_ministerio'  => optional($pastor->start_date_ministry)->format('d/m/Y'),

            'anios_ministerio' => $this->calcularAniosMinisterio($pastor),
        
            // (16) Región
            'region'            => $pastor->region->name ?? '',
        
            // (17) Distrito
            'distrito'          => $pastor->district->name ?? '',
        
            // (18) Sector
            'sector'            => $pastor->sector->name ?? '',
        
            // (19) Estado
            'estado'            => $pastor->state->name ?? '',
        
            // (20) Municipio/Ciudad
            'municipio'            => $pastor->city->name ?? '',
        
            // (21) Tipo de vivienda
            'tipo_vivienda'     => $pastor->housingType->name ?? '',
        
            // (22) Dirección
            'direccion'         => $pastor->address ?? '',
        
            // (23) Género
            'genero'            => $pastor->gender->name ?? '',
        
            // (24) Seguro social (Sí/No)
            'seguro_social'     => $pastor->social_security ? 'Sí' : 'No',
        
            // (25) Política de vivienda (Sí/No)
            'politica_vivienda' => $pastor->housing_policy ? 'Sí' : 'No',
        
            // (26) Otro trabajo (Sí/No)
            'otro_trabajo'      => $pastor->other_work ? 'Sí' : 'No',
        
            // (27) Forma de trabajo
            'forma_trabajo'     => $pastor->how_work ?? '',
        
            // (28) Otros estudios
            'otros_estudios'    => $pastor->other_studies ?? '',
        ];
        

        // DATOS FAMILIARES (families)
        $family = $pastor->families()->where('relation_id', 1)->first();

        // 🔹 Obtener hijos e hijas
        $hijos = $pastor->families()->where('relation_id', 2)->get();
        $totalHijosVarones = $hijos->where('gender_id', 1)->count(); // Masculino
        $totalHijasMujeres = $hijos->where('gender_id', 2)->count(); // Femenino

        // 🔹 Extraer primer nombre (antes del espacio) de cada hijo/hija
        $nombresHijos = $hijos->map(function ($hijo) {
            $nombreCompleto = $hijo->name ?? '';
            return explode(' ', trim($nombreCompleto))[0]; // Primer nombre
        })->filter()->implode(', ');

        if ($family) {
            $datosFamiliares = [
                'nombre_completo_esposa' => ($family->name ?? '') . ' ' . ($family->lastname ?? ''),
                'nacionalidad_esposa'    => $family->nationality->name ?? '',
                'cedula_esposa'          => $family->number_cedula ?? '',
                'tipo_sangre_esposa'     => $family->bloodType->name ?? '',
                'telefono_mobile_esposa' => $family->phone_mobile ?? '',
                'fecha_nacimiento_esposa'=> optional($family->birthdate)->format('d/m/Y'),
                'lugar_nacimiento_esposa'=> $family->birthplace ?? '',
                'tipo_cargo_esposa'      => $family->positionType->name ?? '',
                'cargo_actual_esposa'    => $family->currentPosition->name ?? '',
                'email_esposa'           => $family->email ?? '',
                'nivel_academico_esposa' => $family->academicLevel->name ?? '',
                'carrera_esposa'         => $family->career ?? '',

                // 🔸 Nuevos campos
                'cantidad_hijos_varones' => $totalHijosVarones,
                'cantidad_hijas_mujeres' => $totalHijasMujeres,
                'nombres_hijos' => $nombresHijos,
            ];
        } else {
            // Deja todo vacío
            $datosFamiliares = [
                'nombre_completo_esposa' => '',
                'nacionalidad_esposa'    => '',
                'cedula_esposa'          => '',
                'tipo_sangre_esposa'     => '',
                'telefono_mobile_esposa' => '',
                'fecha_nacimiento_esposa'=> '',
                'lugar_nacimiento_esposa'=> '',
                'tipo_cargo_esposa'      => '',
                'cargo_actual_esposa'    => '',
                'email_esposa'           => '',
                'nivel_academico_esposa' => '',
                'carrera_esposa'         => '',

                // 🔸 Nuevos campos (vacíos)
                'cantidad_hijos_varones' => 0,
                'cantidad_hijas_mujeres' => 0,
                'nombres_hijos' => '',
            ];
        }



        // DATOS MINISTERIALES (pastor_ministries)
        if ($ministry) {
            $datosMinisteriales = [
                'tipo_pastor'         => $ministry->pastorType->name ?? '',
                'pastor_nivel'        => $ministry->pastorLevel->name ?? '',
                'grado_licencia'      => $ministry->pastorLicence->name ?? '',
                'nombramiento_pastoral' => $ministry?->appointment === true ? 'Sí' : 'No',
                'codigo_pastoral'     => $ministry->code_pastor,
                'iglesia_pastorea'    => $ministry->church->name ?? '',
                'codigo_iglesia'      => $ministry->code_church ?? '',
                'tipo_cargo'          => $ministry->positionType->name ?? '',
                'cargo_actual'        => $ministry->currentPosition->name ?? '',
                'egresado_iblc'       => $ministry->iblc ? 'Sí' : 'No',
                'tipo_curso'          => $ministry->courseType->name ?? '',
                'ano_promocion'       => $ministry->promotion_year ?? '',
                'numero_promocion'    => $ministry->promotion_number ?? '',
                'cancela_abisop'      => $ministry->abisop ? 'Sí' : 'No',
            ];
        } else {
            // Deja todo vacío
            $datosMinisteriales = [
                'tipo_pastor'         => '',
                'pastor_nivel'        => '',
                'grado_licencia'      => '',
                'nombramiento_pastoral' => '',
                'codigo_pastoral'     => '',
                'iglesia_pastorea'    => '',
                'codigo_iglesia'      => '',
                'tipo_cargo'          => '',
                'cargo_actual'        => '',
                'egresado_iblc'       => '',
                'tipo_curso'          => '',
                'ano_promocion'       => '',
                'numero_promocion'    => '',
                'cancela_abisop'      => '',
            ];

        }

        // Combinar todo en un solo array
        return array_merge($datosPersonales, $datosFamiliares, $datosMinisteriales);
    }
}