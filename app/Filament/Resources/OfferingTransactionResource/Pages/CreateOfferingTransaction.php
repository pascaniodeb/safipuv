<?php

namespace App\Filament\Resources\OfferingTransactionResource\Pages;

use App\Filament\Resources\OfferingTransactionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOfferingTransaction extends CreateRecord
{
    protected static string $resource = OfferingTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Nuevo Registro'; // Título personalizado
    }
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Registrar Ofrenda')
            ->body('Ofrenda registrada exitosamente.');
    }
}