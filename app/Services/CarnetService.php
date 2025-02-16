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

            $frontTemplatePath = public_path("carnets/designs/{$designType}-front.png");
            $backTemplatePath = public_path("carnets/designs/{$designType}-back.png");

            if (!file_exists($frontTemplatePath) || !file_exists($backTemplatePath)) {
                throw new \Exception("El diseño del carnet {$designType} no existe.");
            }

            // Generar código QR
            $qrCodePath = $this->generateQrCode($pastor);

            // Generar el reverso del carnet
            $outputBackPath = storage_path("app/public/carnets/{$pastor->number_cedula}_carnet_back.png");
            $this->generateBack($backTemplatePath, $qrCodePath, $outputBackPath);

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
        $daysInMinistry = $pastor->start_date_ministry->diffInDays(now());

        $ministry = $pastor->pastorMinistry;

        if (!$ministry) {
            return self::DESIGNS['DEFAULT'];
        }

        $positionType = $ministry->position_type_id ?? null;
        $currentPosition = $ministry->current_position_id ?? null;
        $pastorType = $ministry->pastor_type_id ?? null;

        // Priorizar el diseño de Obispos
        if (in_array($currentPosition, [1, 2])) {
            return self::DESIGNS['OBISPOS'];
        }

        if ($daysInMinistry < 1095 && $positionType === null && $currentPosition === null) {
            return self::DESIGNS['LOCAL_SIN_CARGO'];
        } elseif ($daysInMinistry < 1095 && $currentPosition !== null) {
            return self::DESIGNS['LOCAL'];
        } elseif ($daysInMinistry < 1095 && $pastorType === 3) {
            return self::DESIGNS['LOCAL_ASISTENTE'];
        } elseif ($daysInMinistry > 1095 && $daysInMinistry < 2190 && $positionType === null && $currentPosition === null) {
            return self::DESIGNS['NACIONAL_SIN_CARGO'];
        } elseif ($daysInMinistry > 1095 && $daysInMinistry < 2190 && $currentPosition !== null) {
            return self::DESIGNS['NACIONAL'];
        } elseif ($pastorType === 4) {
            return self::DESIGNS['NACIONAL_VIUDA'];
        } elseif ($daysInMinistry > 2190 && $positionType === null && $currentPosition === null) {
            return self::DESIGNS['ORDENADO_SIN_CARGO'];
        } elseif ($daysInMinistry > 2190 && $currentPosition !== null) {
            return self::DESIGNS['ORDENADO'];
        } elseif ($pastorType === 2) {
            return self::DESIGNS['ORDENADO_ADJUNTO'];
        }

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


    protected function generateBack(string $templatePath, string $qrCodePath, string $outputPath): void
    {
        try {
            // Crear instancia de Imagick con la imagen base
            $image = new \Imagick($templatePath);

            // Cargar la marca de agua (código QR)
            $watermark = new \Imagick($qrCodePath);

            // Redimensionar la marca de agua a un tamaño más manejable (opcional)
            $watermark->resizeImage(200, 200, \Imagick::FILTER_LANCZOS, 1);

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
        // Crear una instancia de Imagick para la plantilla
        $image = new \Imagick($templatePath);

        // Cargar la foto del pastor o usar una foto por defecto
        $photoPath = $pastor->photo_pastor && Storage::exists($pastor->photo_pastor)
            ? Storage::path($pastor->photo_pastor)
            : public_path('images/default-photo.png');

        $pastorPhoto = new \Imagick($photoPath);
        $pastorPhoto->resizeImage(442, 500, \Imagick::FILTER_LANCZOS, 1);

        // Insertar la foto del pastor en la plantilla
        $image->compositeImage($pastorPhoto, \Imagick::COMPOSITE_OVER, 1442, 454);

        // Configuración del texto
        $draw = new \ImagickDraw();
        $draw->setFont(public_path('fonts/arial.TTF')); // Ruta a una fuente válida
        $draw->setFillColor('#FFFFFF');
        $draw->setFontSize(55);

        // Agregar el nombre del pastor
        $image->annotateImage($draw, 482, 565, 0, $pastor->name);

        // Agregar el apellido del pastor
        $image->annotateImage($draw, 482, 488, 0, $pastor->lastname);

        // Cambiar tamaño y color para otros textos
        $draw->setFontSize(55);
        $draw->setFillColor('#FFFFFF');

        // Agregar el número de cédula
        //$image->annotateImage($draw, 487, 595, 0, "Cédula: {$pastor->number_cedula}");
        $image->annotateImage($draw, 482, 642, 0, $pastor->number_cedula);

        // Agregar la licencia pastoral
        $image->annotateImage($draw, 482, 720, 0, $pastor->pastorMinistry->pastorLicence->name ?? 'Sin Licencia');

        // Agregar el nivel pastoral
        $image->annotateImage($draw, 482, 794, 0, $pastor->pastorMinistry->pastorLevel->name ?? 'Sin Nivel');

        // Agregar el cargo actual del pastor (current_position_id)
        $currentPosition = $pastor->pastorMinistry->currentPosition->name ?? 'Sin Cargo';
        $image->annotateImage($draw, 482, 870, 0, $currentPosition);

        // Personalización adicional: Fecha de emisión
        //$draw->setFontSize(15);
        //$draw->setFillColor('#000000');
        //$image->annotateImage($draw, 50, 500, 0, "Fecha de emisión: " . now()->format('d/m/Y'));

        // Guardar la imagen generada
        $image->writeImage($outputPath);

        // Liberar recursos
        $image->clear();
        $image->destroy();
        $pastorPhoto->clear();
        $pastorPhoto->destroy();
    }


}