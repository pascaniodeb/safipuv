<?php

namespace App\Filament\Exports;

use App\Models\Pastor;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PastorsExporter extends Exporter
{
    protected static ?string $model = Pastor::class; // Corregir el nombre del modelo

    /**
     * Forzar el guardado del archivo en `storage/app/public/exports`
     */
    public function storeDisk(): ?string
    {
        return 'public';
    }

    public function storeDirectory(): ?string
    {
        return 'exports';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('name')->label('Nombre'),
            ExportColumn::make('lastname')->label('Apellido'),
            ExportColumn::make('number_cedula')->label('Cédula'),
            ExportColumn::make('email')->label('Correo Electrónico'),
            ExportColumn::make('phone_mobile')->label('Teléfono Móvil'),
            ExportColumn::make('phone_house')->label('Teléfono Casa'),
            ExportColumn::make('birthdate')->label('Fecha de Nacimiento'),
            ExportColumn::make('birthplace')->label('Lugar de Nacimiento'),
            ExportColumn::make('baptism_date')->label('Fecha de Bautismo'),
            ExportColumn::make('who_baptized')->label('Quién Bautizó'),
            ExportColumn::make('start_date_ministry')->label('Inicio Ministerio'),
            ExportColumn::make('career')->label('Carrera Profesional'),
            ExportColumn::make('academicLevel.name')->label('Nivel Académico'),
            ExportColumn::make('other_studies')->label('Otros Estudios'),
            ExportColumn::make('how_work')->label('Cómo Trabaja'),
            ExportColumn::make('other_work')->label('Otros Trabajos')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
            ExportColumn::make('social_security')->label('Seguridad Social')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
            ExportColumn::make('housing_policy')->label('Política Habitacional')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
            ExportColumn::make('housingType.name')->label('Tipo de Vivienda'),
            ExportColumn::make('address')->label('Dirección'),
            ExportColumn::make('region.name')->label('Región'),
            ExportColumn::make('district.name')->label('Distrito'),
            ExportColumn::make('sector.name')->label('Sector'),
            ExportColumn::make('state.name')->label('Estado'),
            ExportColumn::make('city.name')->label('Ciudad'),
            ExportColumn::make('gender.name')->label('Género'),
            ExportColumn::make('nationality.name')->label('Nacionalidad'),
            ExportColumn::make('bloodType.name')->label('Tipo de Sangre'),
            ExportColumn::make('maritalStatus.name')->label('Estado Civil'),
            ExportColumn::make('created_at')->label('Fecha de Creación'),
            ExportColumn::make('updated_at')->label('Última Actualización'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'La exportación de pastores ha finalizado y se exportaron ' . number_format($export->successful_rows) . ' registros.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' registros fallaron en la exportación.';
        }

        return $body;
    }
}