<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait PastorsAccess
{
    

   

    // Método getEloquentQuery (compatible con métodos estáticos)
    public static function getEloquentQuery(): Builder
    {
        $query = self::baseQuery(); // Obtener la consulta base
        $user = auth()->user();

        // Roles Nacionales: Ven todo
        if ($user->hasAnyRole(self::$groups['NACIONAL'])) {
            return $query;
        }

        // Rol Pastor: Solo puede ver su propio registro
        if ($user->hasRole('Pastor')) {
            $pastor = $user->pastor; // Obtener el pastor relacionado con el usuario
            return $pastor ? $query->where('id', $pastor->id) : $query->whereNull('id');
        }

        // Obtener la jurisdicción del usuario
        $jurisdiccion = $user->jurisdiccion;
        if ($jurisdiccion) {
            if ($user->hasAnyRole(self::$groups['REGIONAL'])) {
                return $query->where('region_id', $jurisdiccion->id); // Filtrar por región
            } elseif ($user->hasAnyRole(self::$groups['DISTRITAL'])) {
                return $query->where('district_id', $jurisdiccion->id); // Filtrar por distrito
            } elseif ($user->hasAnyRole(self::$groups['SECTORIAL'])) {
                return $query->where('sector_id', $jurisdiccion->id); // Filtrar por sector
            }
        }

        // Si no hay jurisdicción o rol válido, devolver un conjunto vacío
        return $query->whereNull('id');
    }

    // Método baseQuery (para obtener la consulta base)
    protected static function baseQuery(): Builder
    {
        return app(static::getModel())->newQuery(); // Obtiene la consulta base del modelo
    }
}