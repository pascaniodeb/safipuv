<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use Filament\Notifications\Notification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConversation extends EditRecord
{
    protected static string $resource = ConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->hasAnyRole([
                    'Administrador',
                    'Secretario Nacional',
                    'Tesorero Nacional',
                ])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Editar Conversación'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Conversación')
            ->body('Conversación actualizada exitosamente.');
    }
}