<?php

namespace App\Filament\Resources\OfferingDistributionResource\Pages;

use App\Filament\Resources\OfferingDistributionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOfferingDistribution extends CreateRecord
{
    protected static string $resource = OfferingDistributionResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Nueva Distribución'; // Título personalizado
    }
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Crear Distribución')
            ->body('Distribución creada exitosamente.');
    }
}