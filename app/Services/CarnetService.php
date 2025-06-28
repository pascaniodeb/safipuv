<?php

namespace App\Services;

use App\Models\Pastor;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CarnetService
{
    // Constantes para los diseños de carnets
    const DESIGNS = [
        'LOCAL_SIN_CARGO' => 'local-sincargo',
        'LOCAL' => 'local',
        'LOCAL_ASISTENTE' => 'local-asistente',
        'NACIONAL_SIN_CARGO' => 'nacional-sincargo',
        'NACIONAL' => 'nacional',
        'NACIONAL_ASISTENTE' => 'nacional-asistente',
        'NACIONAL_VIUDA' => 'nacional-viuda',
        'ORDENADO_SIN_CARGO' => 'ordenado-sincargo',
        'ORDENADO' => 'ordenado',
        'ORDENADO_ADJUNTO' => 'ordenado-adjunto',
        'OBISPOS' => 'obispos',
        'DEFAULT' => 'default',
    ];

    public function generateCarnet(Pastor $pastor): array
    {
        try {
            Log::info('Iniciando generación de carnet para el pastor', ['id' => $pastor->id]);

            $designType = $this->determineCarnetType($pastor);
            Log::info('Tipo de diseño determinado', ['designType' => $designType]);

            Log::info('Ruta esperada del diseño FRONT', [
                'ruta' => public_path("carnets/designs/nacional-asistente-front.png"),
            ]);

            $frontTemplatePath = public_path("carnets/designs/{$designType}-front.png");
            $backTemplatePath = public_path("carnets/designs/{$designType}-back.png");

            if (!file_exists($frontTemplatePath)) {
                Log::error("Archivo de diseño frontal no encontrado", ['ruta' => $frontTemplatePath]);
            }
            // Verificar si el archivo de diseño frontal existe
            if (!file_exists($backTemplatePath)) {
                Log::error("Archivo de diseño posterior no encontrado", ['ruta' => $backTemplatePath]);
            }

            if (!file_exists($frontTemplatePath) || !file_exists($backTemplatePath)) {
                throw new \Exception("El diseño del carnet {$designType} no existe.");
            }


            //Log::info('Ruta esperada del diseño FRONT', ['ruta' => $frontTemplatePath]);
            
            //Log::info('Existe?', ['existe' => file_exists($frontTemplatePath) ? 'SÍ' : 'NO']);

            // Generar código QR
            $qrCodePath = $this->generateQrCode($pastor);

            // Generar el reverso del carnet
            //$outputBackPath = storage_path("app/public/carnets/{$pastor->number_cedula}_carnet_back.png");
            //$this->generateBack($backTemplatePath, $qrCodePath, $outputBackPath);

            // Obtener el código del pastor
            $codePastor = $pastor->pastorMinistry->code_pastor ?? 'SIN CÓDIGO';

            // Generar el reverso del carnet
            $outputBackPath = storage_path("app/public/carnets/{$pastor->number_cedula}_carnet_back.png");
            $this->generateBack($backTemplatePath, $qrCodePath, $outputBackPath, $codePastor);

            // Generar el frente del carnet
            $outputFrontPath = storage_path("app/public/carnets/{$pastor->number_cedula}_carnet_front.png");
            $this->generateFront($frontTemplatePath, $pastor, $outputFrontPath);

            Log::info('Carnet generado exitosamente', [
                'front' => $outputFrontPath,
                'back' => $outputBackPath,
            ]);

            return [
                'front' => Storage::url("carnets/{$pastor->number_cedula}_carnet_front.png"),
                'back' => Storage::url("carnets/{$pastor->number_cedula}_carnet_back.png"),
            ];
        } catch (\Exception $e) {
            Log::error('Error al generar el carnet', [
                'message' => $e->getMessage(),
                'pastor_id' => $pastor->id,
            ]);
            session()->flash('error', $e->getMessage());
            return [];
        }
    }

    protected function determineCarnetType(Pastor $pastor): string
    {
        $ministry = $pastor->pastorMinistry;

        if (!$ministry) {
            Log::warning('Pastor sin ministerio. Usando diseño DEFAULT', ['pastor_id' => $pastor->id]);
            return self::DESIGNS['DEFAULT'];
        }

        $incomeId        = $ministry->pastor_income_id;
        $typeId          = $ministry->pastor_type_id;
        $startDate       = $pastor->start_date_ministry;
        $positionTypeId  = $ministry->position_type_id;
        $currentPosition = $ministry->current_position_id;

        // 🔧 Forzar a enteros para que in_array funcione correctamente
        $typeId          = (int) $typeId;
        $positionTypeId  = (int) $positionTypeId;
        $currentPosition = (int) $currentPosition;
        $incomeId        = (int) $incomeId;


        // 1. Obispos (posición de liderazgo)
        if (in_array($currentPosition, [1, 2, 92])) {
            Log::info('Diseño OBISPOS seleccionado');
            return self::DESIGNS['OBISPOS'];
        }

        // 2. Determinar licencia pastoral
        $licenceId = $ministry->pastor_licence_id ?? \App\Services\PastorAssignmentService::determineLicence(
            $incomeId,
            $typeId,
            $startDate
        );

        // 🔧 Cast a enteros para evitar errores de comparación
        $licenceId = (int) $licenceId;
        $typeId = (int) $typeId;
        $positionTypeId = (int) $positionTypeId;
        $currentPosition = (int) $currentPosition;


        Log::info('Valores para determinar el tipo de carnet', [
            'licenceId' => $licenceId,
            'typeId' => $typeId,
            'positionTypeId' => $positionTypeId,
            'currentPosition' => $currentPosition,
        ]);

        // 3. LICENCIA LOCAL
        if ($licenceId === 1) {
            if ($typeId === 3) {
                Log::info('Diseño LOCAL_ASISTENTE seleccionado');
                return self::DESIGNS['LOCAL_ASISTENTE'];
            }

            if ($typeId === 1 && (is_null($positionTypeId) || $positionTypeId == 5)) {
                Log::info('Diseño LOCAL_SIN_CARGO seleccionado');
                return self::DESIGNS['LOCAL_SIN_CARGO'];
            }

            if ($typeId === 1 && in_array($positionTypeId, [1, 2, 3, 4])) {
                Log::info('Diseño LOCAL seleccionado');
                return self::DESIGNS['LOCAL'];
            }
        }

        // 4. LICENCIA NACIONAL
        if ($licenceId === 2) {
            if ($typeId === 3) {
                Log::info('Diseño NACIONAL_ASISTENTE seleccionado');
                return self::DESIGNS['NACIONAL_ASISTENTE'];
            }

            if ($typeId === 4) {
                Log::info('Diseño NACIONAL_VIUDA seleccionado');
                return self::DESIGNS['NACIONAL_VIUDA'];
            }

            if (in_array($typeId, [1, 2]) && (is_null($positionTypeId) || $positionTypeId == 5)) {
                Log::info('Diseño NACIONAL_SIN_CARGO seleccionado');
                return self::DESIGNS['NACIONAL_SIN_CARGO'];
            }

            if (in_array($typeId, [1, 2]) && in_array($positionTypeId, [1, 2, 3, 4])) {
                Log::info('Diseño NACIONAL seleccionado');
                return self::DESIGNS['NACIONAL'];
            }
        }

        // 5. LICENCIA ORDENACIÓN
        if ($licenceId === 3) {
            if ($typeId === 2) {
                Log::info('Diseño ORDENADO_ADJUNTO seleccionado');
                return self::DESIGNS['ORDENADO_ADJUNTO'];
            }

            if (is_null($positionTypeId) || $positionTypeId == 5) {
                Log::info('Diseño ORDENADO_SIN_CARGO seleccionado');
                return self::DESIGNS['ORDENADO_SIN_CARGO'];
            }

            if (in_array($positionTypeId, [1, 2, 3, 4])) {
                Log::info('Diseño ORDENADO seleccionado');
                return self::DESIGNS['ORDENADO'];
            }
        }

        // 6. Fallback explícito
        Log::warning('No se encontró coincidencia en diseño de carnet. Usando DEFAULT', [
            'pastor_id' => $pastor->id,
            'licenceId' => $licenceId,
            'typeId' => $typeId,
            'positionTypeId' => $positionTypeId,
            'currentPosition' => $currentPosition,
        ]);

        return self::DESIGNS['DEFAULT'];
    }







    protected function generateQrCode(Pastor $pastor): string
    {
        $qrData = [
            'lastname' => $pastor->lastname,
            'name' => $pastor->name,
            'number_cedula' => $pastor->number_cedula,
        ];

        $qrCodePath = storage_path("app/public/carnets/{$pastor->number_cedula}_qr.png");

        // Verificar y eliminar el archivo existente si ya existe
        if (file_exists($qrCodePath)) {
            unlink($qrCodePath); // Elimina el archivo existente
        }

        // Generar el QR y guardarlo en la ruta especificada
        QrCode::format('png')->size(300)->generate(json_encode($qrData), $qrCodePath);

        return $qrCodePath;
    }


    protected function generateBack(string $templatePath, string $qrCodePath, string $outputPath, $codePastor): void
    {
        try {
            // Crear instancia de Imagick con la imagen base
            $image = new \Imagick($templatePath);

            // Cargar la marca de agua (código QR)
            $watermark = new \Imagick($qrCodePath);

            // Redimensionar la marca de agua a un tamaño más manejable (opcional)
            $watermark->resizeImage(400, 400, \Imagick::FILTER_LANCZOS, 1);

            // Obtener dimensiones de la imagen base y de la marca de agua
            $imageWidth = $image->getImageWidth();
            $imageHeight = $image->getImageHeight();
            $watermarkWidth = $watermark->getImageWidth();
            $watermarkHeight = $watermark->getImageHeight();

            // Calcular posición para centrar la marca de agua
            $x = ($imageWidth - $watermarkWidth) / 2;
            $y = ($imageHeight - $watermarkHeight) / 2;

            // Componer la marca de agua sobre la imagen base
            $image->compositeImage($watermark, \Imagick::COMPOSITE_OVER, $x, $y);

            // --- Agregar el Código del Pastor debajo del QR ---
            $draw = new \ImagickDraw();
            $draw->setFont(public_path('fonts/arial.TTF')); // Ruta de la fuente
            $draw->setFontSize(55);
            $draw->setFillColor('#000000'); // Color del texto (negro)
            $draw->setTextAlignment(\Imagick::ALIGN_CENTER);

            // Definir la posición debajo del código QR
            $textX = $imageWidth / 2; // Centrado horizontalmente
            $textY = $y + $watermarkHeight + 80; // Posición debajo del QR

            // Dibujar el código del pastor
            $image->annotateImage($draw, $textX, $textY, 0, $codePastor);

            // Guardar la imagen generada
            $image->writeImage($outputPath);

            // Liberar recursos
            $image->clear();
            $image->destroy();
            $watermark->clear();
            $watermark->destroy();

            \Log::info('Imagen generada exitosamente', ['outputPath' => $outputPath]);
        } catch (\Exception $e) {
            // Manejar errores
            \Log::error('Error al generar la imagen: ' . $e->getMessage(), [
                'templatePath' => $templatePath,
                'qrCodePath' => $qrCodePath,
                'outputPath' => $outputPath,
                'exception' => $e
            ]);

            // Opcionalmente, lanzar la excepción
            throw $e;
        }
    }


    protected function generateFront(string $templatePath, Pastor $pastor, string $outputPath): void
    {
        try {
            $image = new \Imagick($templatePath);

            $photoPath = storage_path("app/public/{$pastor->photo_pastor}");

            if ($pastor->photo_pastor && file_exists($photoPath)) {
                Log::info('Foto del pastor encontrada', ['path' => $photoPath]);
            } else {
                Log::warning("Foto del pastor no encontrada, usando la predeterminada.", [
                    'pastor_id' => $pastor->id,
                    'ruta_intentada' => $photoPath,
                    'existe' => file_exists($photoPath)
                ]);
                $photoPath = public_path('images/default-photo.png');
            }

            $pastorPhoto = new \Imagick($photoPath);
            $pastorPhoto->resizeImage(442, 500, \Imagick::FILTER_LANCZOS, 1);
            $image->compositeImage($pastorPhoto, \Imagick::COMPOSITE_OVER, 1442, 454);

            $licenceName = $pastor->pastorMinistry->pastorLicence->name ?? 'Sin Licencia';

            $draw = new \ImagickDraw();
            $draw->setFont(public_path('fonts/arial.TTF'));
            $draw->setFontSize(55);

            if (str_contains(strtolower($licenceName), 'local')) {
                $draw->setFillColor('#000000');
            } else {
                $draw->setFillColor('#FFFFFF');
            }

            $image->annotateImage($draw, 482, 565, 0, $pastor->name);
            $image->annotateImage($draw, 482, 488, 0, $pastor->lastname);
            $image->annotateImage($draw, 482, 642, 0, number_format($pastor->number_cedula, 0, '', '.'));
            $image->annotateImage($draw, 482, 720, 0, $pastor->pastorMinistry->pastorLicence->name ?? 'Sin Licencia');
            $image->annotateImage($draw, 482, 794, 0, $pastor->pastorMinistry->pastorLevel->name ?? 'Sin Nivel');

            $ministry = $pastor->pastorMinistry;
            $typeId = $ministry->pastor_type_id ?? null;
            $position = $ministry->currentPosition->name ?? null;

            if ($typeId === 3 && is_null($position)) {
                $cargoMostrar = 'ASISTENTE';
            } elseif ($position) {
                $cargoMostrar = $position;
            } else {
                $cargoMostrar = '';
            }

            $wrappedText = wordwrap($cargoMostrar, 25, "\n", false);
            $lines = explode("\n", $wrappedText);

            $yPosition = 870;
            $lineSpacing = 55;

            foreach ($lines as $line) {
                $image->annotateImage($draw, 482, $yPosition, 0, $line);
                $yPosition += $lineSpacing;
            }

            Log::info('Texto mostrado como cargo en el carnet', [
                'pastor_id' => $pastor->id,
                'tipo_pastor' => $typeId,
                'cargo' => $cargoMostrar
            ]);

            $image->writeImage($outputPath);

            $image->clear();
            $image->destroy();
            $pastorPhoto->clear();
            $pastorPhoto->destroy();

            Log::info('Frente del carnet generado exitosamente', ['outputPath' => $outputPath]);

        } catch (\Throwable $e) {
            Log::error('Error al generar el frente del carnet', [
                'mensaje' => $e->getMessage(),
                'outputPath' => $outputPath,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    
    
    


}