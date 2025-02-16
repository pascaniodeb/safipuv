<?php

namespace App\Filament\Resources\CurrentPositionResource\Pages;

use App\Filament\Resources\CurrentPositionResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCurrentPosition extends CreateRecord
{
    protected static string $resource = CurrentPositionResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Nuevo Cargo'; // Título personalizado
    }
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Crear Cargo')
            ->body('Cargo creado exitosamente.');
    }
}
