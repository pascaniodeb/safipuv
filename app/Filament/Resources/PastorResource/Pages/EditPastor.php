<?php

namespace App\Filament\Resources\PastorResource\Pages;

use App\Filament\Resources\PastorResource;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPastor extends EditRecord
{
    protected static string $resource = PastorResource::class;

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
        return 'Editar Pastor'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Pastor')
            ->body('Pastor actualizado exitosamente.');
    }
}