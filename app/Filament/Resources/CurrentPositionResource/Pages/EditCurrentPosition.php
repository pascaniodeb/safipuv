<?php

namespace App\Filament\Resources\CurrentPositionResource\Pages;

use App\Filament\Resources\CurrentPositionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurrentPosition extends EditRecord
{
    protected static string $resource = CurrentPositionResource::class;

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
        return 'Editar Cargo'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Cargo')
            ->body('Cargo actualizado exitosamente.');
    }
}
