<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Collection;

class MessagingService
{
    public function createConversation(array $data, User $creator): Conversation
    {
        // Determinar el alcance geográfico basado en el rol del creador
        $scope = $this->determineConversationScope($creator);
        
        $conversation = Conversation::create([
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'creator_id' => $creator->id,
            'sector_id' => $scope['sector_id'] ?? null,
            'district_id' => $scope['district_id'] ?? null,
            'region_id' => $scope['region_id'] ?? null,
            'type' => $data['type'] ?? 'group',
        ]);
        
        // Agregar participantes
        if (!empty($data['participants'])) {
            $conversation->participants()->attach($data['participants'], [
                'joined_at' => now(),
            ]);
        }
        
        // Agregar al creador como participante
        $conversation->participants()->attach($creator->id, [
            'joined_at' => now(),
        ]);
        
        return $conversation;
    }
    
    public function getAvailableParticipants(User $user): Collection
    {
        $query = User::query();
        
        // Filtrar usuarios según el rol y alcance del usuario actual
        switch ($user->role) {
            case 'Administrador':
            case 'Obispo Presidente':
            case 'Tesorero Nacional':
            case 'Contralor Nacional':
                // Pueden comunicarse con todos
                break;
                
            case 'Superintendente Regional':
            case 'Tesorero Regional':
                // Solo con usuarios de su región y nacionales
                $query->where(function($q) use ($user) {
                    $q->where('region_id', $user->region_id)
                      ->orWhereIn('role', ['Administrador', 'Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional']);
                });
                break;
                
            case 'Supervisor Distrital':
                // Solo con usuarios de su distrito, región y nacionales
                $query->where(function($q) use ($user) {
                    $q->where('district_id', $user->district_id)
                      ->orWhere('region_id', $user->region_id)
                      ->orWhereIn('role', ['Administrador', 'Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional']);
                });
                break;
                
            case 'Presbítero Sectorial':
            case 'Tesorero Sectorial':
            case 'Contralor Sectorial':
                // Solo con usuarios de su sector, distrito, región y nacionales
                $query->where(function($q) use ($user) {
                    $q->where('sector_id', $user->sector_id)
                      ->orWhere('district_id', $user->district_id)
                      ->orWhere('region_id', $user->region_id)
                      ->orWhereIn('role', ['Administrador', 'Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional']);
                });
                break;
        }
        
        return $query->where('id', '!=', $user->id)->get();
    }
    
    private function determineConversationScope(User $creator): array
    {
        return [
            'sector_id' => $creator->sector_id,
            'district_id' => $creator->district_id,
            'region_id' => $creator->region_id,
        ];
    }
    
    public function markAsRead(Message $message, User $user): void
    {
        if ($message->sender_id !== $user->id && !$message->read_at) {
            $message->update(['read_at' => now()]);
        }
    }
}