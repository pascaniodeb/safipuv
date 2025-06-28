<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Pastor;
use App\Models\Church;

class AccessControlService
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

    /**
     * Aplica el filtro de acceso a la consulta según el rol del usuario autenticado.
     */
    public static function applyFilters(Builder $query)
    {
        $user = Auth::user();
        $model = $query->getModel();

        if ($model instanceof Pastor) {
            return self::filterPastorsQuery($query, $user);
        }

        if ($model instanceof Church) {
            return self::filterChurchesQuery($query, $user);
        }

        return $query->whereRaw('1 = 0'); // ❌ Bloquear si no tiene permisos
    }

    /**
     * Filtra los pastores según el rol del usuario autenticado.
     */
    protected static function filterPastorsQuery(Builder $query, $user = null)
    {
        // 🔹 Si no se pasa el usuario, tomar el autenticado (solo si existe)
        $user = $user ?? Auth::user();

        // 🔒 Si no hay usuario autenticado (por ejemplo, en consola), devolver todo sin filtrar
        if (! $user) {
            return $query;
        }

        // 🔹 Si el usuario es Pastor (solo ve sus propios datos)
        if ($user->hasRole('Pastor')) {
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('id', $user->id);
            });
        }

        // 🔹 Rol nacional: ve todo
        if ($user->hasAnyRole(self::$nationalRoles)) {
            return $query;
        }

        // 🔹 Rol regional: filtra por región
        if ($user->hasAnyRole(self::$regionalRoles)) {
            return $query->where('region_id', $user->region_id);
        }

        // 🔹 Supervisor Distrital: filtra por distrito
        if ($user->hasRole('Supervisor Distrital')) {
            return $query->where('district_id', $user->district_id);
        }

        // 🔹 Rol sectorial: filtra por sector
        if ($user->hasAnyRole(self::$sectorRoles)) {
            return $query->where('sector_id', $user->sector_id);
        }

        // ❌ Si no tiene ningún rol válido, bloquear
        return $query->whereRaw('1 = 0');
    }


    /**
     * Filtra las iglesias según el rol del usuario autenticado.
     */
    protected static function filterChurchesQuery(Builder $query, $user)
    {
        // 🔹 Obtener el usuario autenticado
        $user = Auth::user();

        // 🔒 Si no hay usuario autenticado (por ejemplo, en consola), devolver todo sin filtrar
        if (! $user) {
            return $query;
        }


        // 🔹 Si el usuario es un Pastor, solo puede ver la iglesia que pastorea
        if ($user->hasRole('Pastor')) {
            return $query->whereHas('ministries', function ($q) use ($user) {
                $q->whereHas('pastor', function ($p) use ($user) {
                    $p->whereHas('user', function ($u) use ($user) {
                        $u->where('id', $user->id);
                    });
                });
            });
        }


        // 🔹 Si el usuario tiene un rol nacional, puede ver todas las iglesias
        if ($user->hasAnyRole(self::$nationalRoles)) {
            return $query;
        }

        // 🔹 Si el usuario tiene un rol regional, filtrar por región
        if ($user->hasAnyRole(self::$regionalRoles)) {
            return $query->where('region_id', $user->region_id);
        }

        // 🔹 Si el usuario es Supervisor Distrital, filtrar por distrito
        if ($user->hasRole('Supervisor Distrital')) {
            return $query->where('district_id', $user->district_id);
        }

        // 🔹 Si el usuario tiene un rol sectorial, filtrar por sector
        if ($user->hasAnyRole(self::$sectorRoles)) {
            return $query->where('sector_id', $user->sector_id);
        }

        return $query->whereRaw('1 = 0'); // ❌ Bloquear si no tiene permisos
    }
}