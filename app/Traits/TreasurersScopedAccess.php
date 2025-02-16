<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait TreasurersScopedAccess
{
    public static function canViewAny(): bool
    {
        \Log::info('canViewAny evaluated for user: ' . auth()->user()->id);
        \Log::info('User roles: ' . implode(',', auth()->user()->roles->pluck('name')->toArray()));

        return auth()->user()->hasRole([
            'Administrador',
            'Tesorero Nacional',
            'Tesorero Regional',
            'Supervisor Distrital',
            'Tesorero Sectorial',
        ]);
    }


    public static function canCreate(): bool
    {
        return auth()->user()->hasRole([
            'Administrador',
            'Tesorero Nacional',
            'Tesorero Regional',
            'Supervisor Distrital',
            'Tesorero Sectorial',
        ]);
    }

    public static function canEdit(Model $record): bool
    {
        // Los roles Administrador y Tesorero Nacional pueden editar cualquier registro
        if (auth()->user()->hasRole(['Administrador', 'Tesorero Nacional'])) {
            return true;
        }

        // Otros roles solo pueden editar sus propios registros
        if (auth()->user()->hasRole(['Tesorero Regional', 'Supervisor Distrital', 'Tesorero Sectorial'])) {
            return $record->user_id === auth()->id();
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        // Solo el Administrador y el Tesorero Nacional pueden eliminar
        return auth()->user()->hasRole(['Administrador', 'Tesorero Nacional']);
    }
}