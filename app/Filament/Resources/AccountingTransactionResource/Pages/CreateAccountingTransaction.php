<?php

namespace App\Filament\Resources\AccountingTransactionResource\Pages;

use App\Filament\Resources\AccountingTransactionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountingTransaction extends CreateRecord
{
    protected static string $resource = AccountingTransactionResource::class;


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
            ->title('Nuevo Registro')
            ->body('Registro creado exitosamente.');
    }
}