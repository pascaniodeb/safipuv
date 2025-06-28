<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // El filtro se maneja en el scope del modelo
    }
    
    public function view(User $user, Conversation $conversation): bool
    {
        // Verificar si el usuario tiene acceso según su rol y ubicación
        return $this->hasAccessToConversation($user, $conversation);
    }
    
    public function create(User $user): bool
    {
        return true; // Todos los usuarios pueden crear conversaciones
    }
    
    public function update(User $user, Conversation $conversation): bool
    {
        // Solo el creador o usuarios de nivel superior pueden editar
        return $user->id === $conversation->creator_id || 
               $this->isHigherRole($user, $conversation->creator);
    }
    
    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->creator_id || 
               in_array($user->role, ['Administrador', 'Obispo Presidente']);
    }
    
    private function hasAccessToConversation(User $user, Conversation $conversation): bool
    {
        // Usuarios nacionales pueden ver todo
        if (in_array($user->role, ['Administrador', 'Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional'])) {
            return true;
        }
        
        // Verificar si es participante
        if ($conversation->participants()->where('user_id', $user->id)->exists()) {
            return true;
        }
        
        // Verificar alcance geográfico
        if ($user->region_id && $conversation->region_id === $user->region_id) {
            return true;
        }
        
        if ($user->district_id && $conversation->district_id === $user->district_id) {
            return true;
        }
        
        if ($user->sector_id && $conversation->sector_id === $user->sector_id) {
            return true;
        }
        
        return false;
    }
    
    private function isHigherRole(User $user, User $other): bool
    {
        $hierarchy = [
            'Administrador' => 10,
            'Obispo Presidente' => 9,
            'Tesorero Nacional' => 8,
            'Contralor Nacional' => 8,
            'Superintendente Regional' => 6,
            'Tesorero Regional' => 5,
            'Supervisor Distrital' => 4,
            'Presbítero Sectorial' => 3,
            'Tesorero Sectorial' => 2,
            'Contralor Sectorial' => 2,
        ];
        
        return ($hierarchy[$user->role] ?? 0) > ($hierarchy[$other->role] ?? 0);
    }
}