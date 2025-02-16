<?php

namespace App\Policies;

use App\Models\User;

class TreasurerNationalPolicy
{
    /**
     * Verificar si el usuario puede ver cualquier recurso relacionado.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Tesorero Nacional');
    }

    /**
     * Verificar si el usuario puede ver un recurso específico.
     */
    public function view(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Tesorero Nacional');
    }

    /**
     * Verificar si el usuario puede crear un recurso.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Tesorero Nacional');
    }

    /**
     * Verificar si el usuario puede actualizar un recurso.
     */
    public function update(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->hasRole('Tesorero Nacional');
    }

    /**
     * Verificar si el usuario puede eliminar un recurso.
     */
    public function delete(User $user): bool
    {
        return $user->hasRole('Administrador');
    }
}