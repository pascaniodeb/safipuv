<?php

namespace App\Filament\Resources\PastorLevelResource\Pages;

use App\Filament\Resources\PastorLevelResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPastorLevel extends EditRecord
{
    protected static string $resource = PastorLevelResource::class;

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
        return 'Editar Nivel'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Nivel')
            ->body('Nivel actualizado exitosamente.');
    }
}
