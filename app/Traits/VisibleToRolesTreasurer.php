<?php

namespace App\Traits;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

trait VisibleToRolesTreasurer
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Definir los roles que pueden ver este recurso
        $allowedRoles = [
            'Administrador',
            'Tesorero Sectorial',
            'Contralor Sectorial',
            'Supervisor Distrital',
            'Tesorero Regional',
            'Contralor Regional',
            'Tesorero Nacional',
            'Contralor Nacional',
            'Obispo Presidente',
        ];

        // Verificar si el usuario tiene alguno de los roles permitidos
        return $user->hasAnyRole($allowedRoles);
    }
}