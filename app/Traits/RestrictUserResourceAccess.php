<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait RestrictUserResourceAccess
{
    public static function canViewAny(): bool
    {
        // Cualquier usuario autenticado puede ver la lista de usuarios (al menos su propio perfil)
        return Auth::check(); 
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasAnyRole(['Administrador', 'Secretario Nacional', 'Tesorero Nacional']);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Permitir la edición para Administrador, Secretario Nacional y Tesorero Nacional
        if ($user->hasAnyRole(['Administrador', 'Secretario Nacional', 'Tesorero Nacional'])) {
            return true;
        }

        // Para otros roles, solo permitir la edición del propio usuario
        return $record->id === $user->id; 
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->hasRole('Administrador');
    }

    public static function getNavigationGroup(): ?string
    {
        return Auth::user()?->hasAnyRole(['Administrador', 'Obispo Presidente', 'Secretario Nacional', 'Tesorero Nacional']) ? 'Usuarios' : null;
    }
}