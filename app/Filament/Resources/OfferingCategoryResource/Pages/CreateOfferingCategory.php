<?php

namespace App\Filament\Resources\OfferingCategoryResource\Pages;

use App\Filament\Resources\OfferingCategoryResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOfferingCategory extends CreateRecord
{
    protected static string $resource = OfferingCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Nueva Ofrenda'; // Título personalizado
    }

    
    
    protected function getCreatedNotification(): ?Notification
    {
        $recipient = auth()->user();
        
        return Notification::make()
            ->success()
            ->title('Crear Ofrenda')
            ->body('Ofrenda creada exitosamente.')
            ->sendToDatabase($recipient);
    }
}