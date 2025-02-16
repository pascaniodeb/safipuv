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
        
    ];

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        if ($user->hasAnyRole(array_merge(
            self::$nationalRoles,
            self::$regionalRoles,
            self::$districtRoles,
            self::$sectorRoles,
            ['Pastor']
        ))) {
            return true;
        }

        return false;
    }

    public static function canView($record): bool
    {
        $user = Auth::user();
        $model = get_class($record);

        // 🔹 Si el usuario es un Pastor y el registro es su propio perfil
        if ($model === \App\Models\Pastor::class && $user->hasRole('Pastor') && $user->pastor->id === $record->id) {
            return true;
        }

        // 🔹 Si el usuario es un Pastor y el registro es la iglesia que pastorea (desde `pastorMinistry`)
        if ($model === \App\Models\Church::class && $user->hasRole('Pastor') && $user->pastor->pastorMinistry->church_id === $record->id) {
            return true;
        }

        // Si es un administrador, obispo o tiene roles con mayor autoridad, puede ver todos
        if ($user->hasAnyRole(array_merge(
            self::$nationalRoles,
            self::$regionalRoles,
            self::$districtRoles,
            self::$sectorRoles
        ))) {
            return true;
        }

        return false;
    }

    public static function scopeAccessControlQuery(Builder $query): Builder
    {
        $user = Auth::user();
        $model = $query->getModel();

        // 🔹 Si estamos en la tabla `pastors`, los pastores solo ven su propio registro
        if ($model instanceof \App\Models\Pastor) {
            if ($user->hasRole('Pastor') && $user->pastor) {
                return $query->where('id', $user->pastor->id);
            }
        }

        // 🔹 Si estamos en la tabla `churches`, los pastores solo ven la iglesia que pastorean
        if ($model instanceof \App\Models\Church) {
            $pastorMinistry = $user->pastor->pastorMinistry ?? null;

            if ($user->hasRole('Pastor') && $pastorMinistry && $pastorMinistry->church_id) {
                return $query->where('id', $pastorMinistry->church_id);
            }

            // Si no tiene una iglesia asignada, retornamos una consulta vacía
            return $query->whereRaw('1 = 0');
        }
        
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
        $model = get_class($record);

        // 🔹 Si el usuario es un Pastor, puede editar su propio perfil
        if ($model === \App\Models\Pastor::class && $user->hasRole('Pastor') && $user->pastor->id === $record->id) {
            return true;
        }

        // 🔹 Si el usuario es un Pastor, puede editar la iglesia que pastorea (desde `pastorMinistry`)
        if ($model === \App\Models\Church::class && $user->hasRole('Pastor') && $user->pastor->pastorMinistry->church_id === $record->id) {
            return true;
        }
        
        
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