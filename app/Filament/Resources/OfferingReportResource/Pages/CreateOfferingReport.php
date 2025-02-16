<?php

namespace App\Filament\Resources\OfferingReportResource\Pages;

use App\Filament\Resources\OfferingReportResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOfferingReport extends CreateRecord
{
    protected static string $resource = OfferingReportResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Nuevo Reporte'; // Título personalizado
    }
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Crear Reporte')
            ->body('Reporte creado exitosamente.');
    }
}