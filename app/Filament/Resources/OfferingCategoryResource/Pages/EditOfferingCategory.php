<?php

namespace App\Filament\Resources\OfferingCategoryResource\Pages;

use App\Filament\Resources\OfferingCategoryResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfferingCategory extends EditRecord
{
    protected static string $resource = OfferingCategoryResource::class;

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
        return 'Editar Ofrenda'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        
        $recipient = auth()->user();
        
        return Notification::make()
            ->success()
            ->title('Editar Ofrenda')
            ->body('Ofrenda actualizada exitosamente.')
            ->sendToDatabase($recipient);
    }

    
}