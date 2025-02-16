<?php

namespace App\Traits;

trait RestrictToAdmin
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('Administrador');
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('Administrador');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('Administrador');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('Administrador');
    }

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('Administrador') ? 'Administración' : null;
    }
}