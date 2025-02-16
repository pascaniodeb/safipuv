<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ChurchAccess
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
        'Pastor',
    ];

    // Método canViewAny (estático)
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user->hasAnyRole(array_merge(
            self::$nationalRoles,
            self::$regionalRoles,
            self::$districtRoles,
            self::$sectorRoles
        ));
    }

    // Método getEloquentQuery (compatible con métodos estáticos)
    public static function getEloquentQuery(): Builder
    {
        $query = self::baseQuery(); // Obtener la consulta base
        $user = auth()->user();

        if ($user->hasAnyRole(self::$nationalRoles)) {
            return $query; // Los roles nacionales ven todas las iglesias
        }

        // Si es un pastor estándar, filtrar por las iglesias que pastorea
        if ($user->hasRole('Pastor')) {
            $pastor = $user->pastor; // Obtener el pastor relacionado con el usuario
            if ($pastor) {
                $churchIds = $pastor->ministries->pluck('church_id')->unique()->toArray();
                return $query->whereIn('id', $churchIds); // Filtrar por iglesias relacionadas
            }
            return $query->where('id', null); // Devolver un conjunto vacío si no hay pastor
        }

        $jurisdiccion = $user->jurisdiccion;
        if ($jurisdiccion) {
            if ($user->hasAnyRole(self::$regionalRoles)) {
                return $query->where('region_id', $jurisdiccion->id);
            } elseif ($user->hasAnyRole(self::$districtRoles)) {
                return $query->where('district_id', $jurisdiccion->id);
            } elseif ($user->hasAnyRole(self::$sectorRoles)) {
                return $query->where('sector_id', $jurisdiccion->id);
            }
        }

        return $query->where('id', null); // Devolver un conjunto vacío si no hay coincidencias
    }

    // Método baseQuery (para obtener la consulta base)
    protected static function baseQuery(): Builder
    {
        return app(static::getModel())->newQuery(); // Obtiene la consulta base del modelo
    }

    // Método canCreate (estático)
    public static function canCreate(array $data = []): bool
    {
        $user = auth()->user();

        if ($user->hasAnyRole(self::$nationalRoles)) {
            return true; // Roles nacionales pueden crear cualquier registro
        }

        if ($user->hasRole('Pastor')) {
            return false; // Pastores estándar no pueden crear
        }

        $jurisdiccion = $user->jurisdiccion;
        if ($jurisdiccion) {
            if ($user->hasAnyRole(self::$regionalRoles)) {
                return isset($data['region_id']) && $data['region_id'] === $jurisdiccion->id;
            } elseif ($user->hasAnyRole(self::$districtRoles)) {
                return isset($data['district_id']) && $data['district_id'] === $jurisdiccion->id;
            } elseif ($user->hasAnyRole(self::$sectorRoles)) {
                return isset($data['sector_id']) && $data['sector_id'] === $jurisdiccion->id;
            }
        }

        return false; // Por defecto, no se permite la creación
    }

    // Método canEdit (estático)
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();

        if ($user->hasAnyRole(self::$nationalRoles)) {
            return true; // Roles nacionales pueden editar cualquier registro
        }

        if ($user->hasRole('Pastor')) {
            $pastor = $user->pastor; // Obtener el pastor relacionado con el usuario
            if ($pastor) {
                $churchIds = $pastor->ministries->pluck('church_id')->unique()->toArray();
                return in_array($record->id, $churchIds);
            }
            return false; // No hay pastor asociado
        }

        $jurisdiccion = $user->jurisdiccion;
        if ($jurisdiccion) {
            if ($user->hasAnyRole(self::$regionalRoles)) {
                return $record->region_id === $jurisdiccion->id;
            } elseif ($user->hasAnyRole(self::$districtRoles)) {
                return $record->district_id === $jurisdiccion->id;
            } elseif ($user->hasAnyRole(self::$sectorRoles)) {
                return $record->sector_id === $jurisdiccion->id;
            }
        }

        return false; // Por defecto, no se permite la edición
    }

    // Método canDelete (estático)
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        return $user->hasAnyRole(self::$nationalRoles); // Solo roles nacionales pueden eliminar
    }

    // Método canView (estático)
    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();

        if ($user->hasAnyRole(self::$nationalRoles)) {
            return true; // Roles nacionales pueden ver cualquier registro
        }

        if ($user->hasRole('Pastor')) {
            $pastor = $user->pastor; // Obtener el pastor relacionado con el usuario
            if ($pastor) {
                $churchIds = $pastor->ministries->pluck('church_id')->unique()->toArray();
                return in_array($record->id, $churchIds);
            }
            return false; // No hay pastor asociado
        }

        $jurisdiccion = $user->jurisdiccion;
        if ($jurisdiccion) {
            if ($user->hasAnyRole(self::$regionalRoles)) {
                return $record->region_id === $jurisdiccion->id;
            } elseif ($user->hasAnyRole(self::$districtRoles)) {
                return $record->district_id === $jurisdiccion->id;
            } elseif ($user->hasAnyRole(self::$sectorRoles)) {
                return $record->sector_id === $jurisdiccion->id;
            }
        }

        return false; // Por defecto, no se permite ver el registro
    }
}