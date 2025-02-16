<?php

namespace App\Filament\Resources\OfferingTransactionResource\Pages;

use App\Filament\Resources\OfferingTransactionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfferingTransaction extends EditRecord
{
    protected static string $resource = OfferingTransactionResource::class;

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
        return 'Editar Registro'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Registro')
            ->body('Ofrenda actualizada exitosamente.');
    }
}