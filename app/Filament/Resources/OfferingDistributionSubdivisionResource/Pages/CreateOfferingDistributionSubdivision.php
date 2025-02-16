<?php

namespace App\Filament\Resources\OfferingDistributionSubdivisionResource\Pages;

use App\Filament\Resources\OfferingDistributionSubdivisionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOfferingDistributionSubdivision extends CreateRecord
{
    protected static string $resource = OfferingDistributionSubdivisionResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Nueva Sub-Deducción'; // Título personalizado
    }
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Crear Sub-Deducción')
            ->body('Sub-Deducción creada exitosamente.');
    }
}