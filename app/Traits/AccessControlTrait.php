<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

trait AccessControlTrait
{
    protected static $nationalRoles = [
        'Administrador',
        'Obispo Presidente',
        'Obispo Vicepresidente',
        'Secretario Nacional',
        'Tesorero Nacional',
        'Contralor Nacional',
        'Inspector Nacional',
        'Directivo Nacional',
    ];

    protected static $regionalRoles = [
        'Superintendente Regional',
        'Secretario Regional',
        'Tesorero Regional',
        'Contralor Regional',
        'Inspector Regional',
        'Directivo Regional',
    ];

    protected static $districtRoles = [
        'Supervisor Distrital',
    ];

    protected static $sectorRoles = [
        'Presbítero Sectorial',
        'Secretario Sectorial',
        'Tesorero Sectorial',
        'Contralor Sectorial',
        'Directivo Sectorial',
        'Usuario Estandar',
    ];

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        if ($user->hasAnyRole(array_merge(
            self::$nationalRoles,
            self::$regionalRoles,
            self::$districtRoles,
            self::$sectorRoles,
            ['Pastor Sectorial']
        ))) {
            return true;
        }

        return false;
    }

    public static function scopeAccessControlQuery(Builder $query): Builder
    {
        $user = Auth::user();

        // Acceso completo para roles nacionales
        if ($user->hasAnyRole(self::$nationalRoles)) {
            return $query;
        }

        // Filtrar registros por región para roles regionales
        if ($user->hasRole(self::$regionalRoles)) {
            return $query->where('region_id', $user->region_id);
        }

        // Filtrar registros por distrito
        if ($user->hasRole('Supervisor Distrital')) {
            return $query->where('district_id', $user->district_id);
        }

        // Filtrar registros por sector
        if ($user->hasAnyRole(self::$sectorRoles)) {
            return $query->where('sector_id', $user->sector_id);
        }

        // Acceso restringido por defecto
        return $query;
    }


    public static function canCreate(): bool
    {
        $user = Auth::user();

        if ($user->hasAnyRole(array_merge(
            ['Administrador', 'Secretario Nacional', 'Tesorero Nacional'],
            ['Secretario Regional', 'Tesorero Regional', 'Usuario Estandar'],
            ['Presbítero Sectorial', 'Secretario Sectorial', 'Tesorero Sectorial']
        ))) {
            return true;
        }

        return false;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['Administrador', 'Obispo Presidente', 'Secretario Nacional', 'Tesorero Nacional']))
        {
            return true;
        }

        if ($user->hasRole('Superintendente Regional') && $record->region_id === $user->region_id) {
            if ($record instanceof Role && in_array($record->name, self::$nationalRoles)) {
                return false;
            }
            return true;
        }

        if ($user->hasRole('Supervisor Distrital') && $record->district_id === $user->district_id) {
            if ($record instanceof Role && in_array($record->name, self::$nationalRoles)) {
                return false;
            }
            return true;
        }

        if ($user->hasAnyRole(['Secretario Regional', 'Tesorero Regional']) && $record->region_id === $user->region_id) {
            if ($record instanceof Role && in_array($record->name, self::$nationalRoles)) {
                return false;
            }
            return true;
        }

        if ($user->hasAnyRole(['Presbítero Sectorial', 'Secretario Sectorial', 'Tesorero Sectorial']) && $record->sector_id === $user->sector_id) {
            if ($record instanceof Role && in_array($record->name, array_merge(self::$nationalRoles, self::$regionalRoles))) {
                return false;
            }
            return true;
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        if ($user->hasRole('Administrador')) {
            return true;
        }

        return false;
    }
}