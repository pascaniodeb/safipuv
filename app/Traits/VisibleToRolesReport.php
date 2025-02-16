<?php

namespace App\Traits;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

trait VisibleToRolesReport
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Definir los roles que pueden ver este recurso
        $allowedRoles = ['Administrador', 'Tesorero Sectorial', 'Supervisor Distrital', 'Tesorero Regional', 'Tesorero Nacional'];

        // Verificar si el usuario tiene alguno de los roles permitidos
        return $user->hasAnyRole($allowedRoles);
    }
}