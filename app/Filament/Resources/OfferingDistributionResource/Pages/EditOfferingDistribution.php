<?php

namespace App\Filament\Resources\OfferingDistributionResource\Pages;

use App\Filament\Resources\OfferingDistributionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfferingDistribution extends EditRecord
{
    protected static string $resource = OfferingDistributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Editar Distribución'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Distribución')
            ->body('Distribución actualizada exitosamente.');
    }
}