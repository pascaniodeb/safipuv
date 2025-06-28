<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use App\Services\MessagingService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateConversation extends CreateRecord
{
    protected static string $resource = ConversationResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Establecer el creator_id del usuario autenticado
        $data['creator_id'] = auth()->id();
        
        // Determinar el alcance geográfico basado en el rol del creador
        $user = auth()->user();
        $data['sector_id'] = $user->sector_id;
        $data['district_id'] = $user->district_id;
        $data['region_id'] = $user->region_id;
        
        // Validar conversación privada
        if ($data['type'] === 'private' && count($data['participants']) > 1) {
            Notification::make()
                ->title('Error de validación')
                ->body('Una conversación privada solo puede tener un participante además del creador.')
                ->danger()
                ->send();
            
            $this->halt();
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        $conversation = $this->record;
        $participants = $this->form->getState()['participants'] ?? [];
        
        // Agregar participantes a la conversación
        if (!empty($participants)) {
            $conversation->participants()->attach($participants, [
                'joined_at' => now(),
            ]);
        }
        
        // Agregar al creador como participante si no está ya incluido
        if (!in_array(auth()->id(), $participants)) {
            $conversation->participants()->attach(auth()->id(), [
                'joined_at' => now(),
            ]);
        }

        // Crear mensaje inicial si hay descripción
        if ($conversation->description) {
            $conversation->messages()->create([
                'sender_id' => auth()->id(),
                'content' => $conversation->description,
                'type' => 'text',
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    public function getTitle(): string
    {
        return 'Nueva Conversación';
    }
}