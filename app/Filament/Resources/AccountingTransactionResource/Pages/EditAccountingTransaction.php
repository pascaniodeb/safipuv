<?php

namespace App\Filament\Resources\AccountingTransactionResource\Pages;

use App\Filament\Resources\AccountingTransactionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountingTransaction extends EditRecord
{
    protected static string $resource = AccountingTransactionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $accounting = \App\Models\Accounting::find($data['accounting_id']);

        $data['region_id'] = $accounting->region_id;
        $data['district_id'] = $accounting->district_id;
        $data['sector_id'] = $accounting->sector_id;

        return $data;
    }


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
            ->body('Registro actualizado exitosamente.');
    }
}