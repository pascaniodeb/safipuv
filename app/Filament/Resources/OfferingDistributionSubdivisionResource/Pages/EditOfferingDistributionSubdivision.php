<?php

namespace App\Filament\Resources\OfferingDistributionSubdivisionResource\Pages;

use App\Filament\Resources\OfferingDistributionSubdivisionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfferingDistributionSubdivision extends EditRecord
{
    protected static string $resource = OfferingDistributionSubdivisionResource::class;

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
        return 'Editar Sub-Deducción'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Sub-Deducción')
            ->body('Sub-Deducción actualizada exitosamente.');
    }
}