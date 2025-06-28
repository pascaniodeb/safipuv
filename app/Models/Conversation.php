<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'subject',
        'description',
        'creator_id',
        'sector_id',
        'district_id',
        'region_id',
        'status',
        'type'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withPivot(['joined_at', 'last_read_at', 'is_active'])
                    ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // Scope para filtrar conversaciones según el rol del usuario
    public function scopeVisibleTo($query, User $user)
    {
        $role = $user->role;
        
        // Usuarios nacionales pueden ver todo
        if (in_array($role, ['Administrador', 'Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional'])) {
            return $query;
        }
        
        // Usuarios regionales solo ven su región
        if (in_array($role, ['Superintendente Regional', 'Tesorero Regional'])) {
            return $query->where('region_id', $user->region_id);
        }
        
        // Usuarios distritales solo ven su distrito
        if (in_array($role, ['Supervisor Distrital'])) {
            return $query->where('district_id', $user->district_id);
        }
        
        // Usuarios sectoriales solo ven su sector
        if (in_array($role, ['Presbítero Sectorial', 'Tesorero Sectorial', 'Contralor Sectorial'])) {
            return $query->where('sector_id', $user->sector_id);
        }
        
        return $query->whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}