<?php

namespace App\Filament\Resources\PastorLevelResource\Pages;

use App\Filament\Resources\PastorLevelResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePastorLevel extends CreateRecord
{
    protected static string $resource = PastorLevelResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Nuevo Nivel'; // Título personalizado
    }
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Crear Nivel')
            ->body('Nivel creado exitosamente.');
    }
}
