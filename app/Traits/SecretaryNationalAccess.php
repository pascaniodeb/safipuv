<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait SecretaryNationalAccess
{
    /**
     * Verifica si el usuario tiene acceso como Administrador o Tesorero Nacional.
     *
     * @return bool
     */
    public function hasSecretaryNationalAccess(): bool
    {
        $user = Auth::user();

        return $user->hasRole('Administrador') || $user->hasRole('Secretario Nacional');
    }

    /**
     * Define si el recurso puede ser visualizado por cualquier usuario.
     *
     * @return bool
     */
    public static function canViewAny(): bool
    {
        return (new static)->hasSecretaryNationalAccess();
    }

    /**
     * Define si el recurso puede ser creado por el usuario.
     *
     * @return bool
     */
    public static function canCreate(): bool
    {
        return (new static)->hasSecretaryNationalAccess();
    }

    /**
     * Define si el recurso puede ser editado por el usuario.
     *
     * @return bool
     */
    public static function canEdit(Model $record): bool
    {
        return (new static)->hasSecretaryNationalAccess();
    }

    /**
     * Define si el recurso puede ser eliminado por el usuario.
     *
     * @return bool
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        // Solo el Administrador puede eliminar
        return $user->hasRole('Administrador');
    }
}