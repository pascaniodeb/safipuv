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
            'Obispo Presidente',
            'Obispo Viceresidente',
            'Tesorero Nacional',
            'Contralor Nacional',
            'Superintendente Regional',
            'Tesorero Regional',
            'Contralor Regional',
            'Supervisor Distrital',
            'Presbítero Sectorial',
            'Tesorero Sectorial',
            'Contralor Sectorial',
        ];

        // Verificar si el usuario tiene alguno de los roles permitidos
        return $user->hasAnyRole($allowedRoles);
    }
}