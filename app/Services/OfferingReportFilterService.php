<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OfferingReportFilterService
{
    public static function applyFilters(Builder $query): Builder
    {
        $user = Auth::user();

        // 📌 Si el usuario tiene un rol nacional, puede ver todos los registros
        $nationalRoles = ['Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional', 'Administrador'];
        if ($user->hasAnyRole($nationalRoles)) {
            return $query; // 🔹 Permite ver todos los registros
        }

        // 📌 Si el usuario tiene un rol regional, filtrar por región
        $regionalRoles = ['Superintendente Regional', 'Secretario Regional', 'Tesorero Regional', 'Contralor Regional', 'Inspector Regional'];
        if ($user->hasAnyRole($regionalRoles)) {
            return $query->whereHas('pastor', function ($q) use ($user) {
                $q->where('region_id', $user->region_id);
            });
        }

        // 📌 Si el usuario tiene el rol de Supervisor Distrital, filtrar por distrito
        if ($user->hasRole('Supervisor Distrital')) {
            return $query->whereHas('pastor', function ($q) use ($user) {
                $q->where('district_id', $user->district_id);
            });
        }

        // 📌 Si el usuario tiene un rol sectorial, filtrar por sector
        $sectorRoles = ['Presbítero Sectorial', 'Secretario Sectorial', 'Contralor Sectorial', 'Tesorero Sectorial'];
        if ($user->hasAnyRole($sectorRoles)) {
            return $query->whereHas('pastor', function ($q) use ($user) {
                $q->where('sector_id', $user->sector_id);
            });
        }

        // 📌 Si el usuario es un Pastor, solo ve sus propios registros
        if ($user->hasRole('Pastor')) {
            return $query->whereHas('pastor', function ($q) use ($user) {
                $q->whereHas('user', function ($u) use ($user) {
                    $u->where('id', $user->id);
                });
            });
        }
        

        // 📌 Si el usuario no tiene roles adecuados, no ve nada
        return $query->whereRaw('1 = 0');
    }
}