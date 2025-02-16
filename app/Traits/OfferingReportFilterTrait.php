<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait OfferingReportFilterTrait
{
    public static function getFilteredQuery(): Builder
    {
        $query = static::getModel()::query(); // ✅ CORREGIDO

        $user = Auth::user();

        // Rol: Pastor (solo ve sus propias ofrendas)
        if ($user->hasRole('Pastor')) {
            return $query->where('pastor_id', $user->id);
        }

        // Roles sectoriales
        $sectorRoles = ['Presbitero Sectorial', 'Secretario Sectorial', 'Contralor Sectorial'];
        if ($user->hasAnyRole($sectorRoles)) {
            return $query->whereHas('pastor', function ($q) use ($user) {
                $q->where('sector_id', $user->sector_id);
            });
        }

        // Rol: Tesorero Sectorial (puede crear y editar)
        if ($user->hasRole('Tesorero Sectorial')) {
            return $query->whereHas('pastor', function ($q) use ($user) {
                $q->where('sector_id', $user->sector_id);
            });
        }

        // Rol: Supervisor Distrital (solo ve los registros de su distrito)
        if ($user->hasRole('Supervisor Distrital')) {
            return $query->whereHas('pastor', function ($q) use ($user) {
                $q->where('district_id', $user->district_id);
            });
        }

        // Roles regionales
        $regionalRoles = ['Superintendente Regional', 'Secretario Regional', 'Tesorero Regional', 'Contralor Regional', 'Inspector Regional'];
        if ($user->hasAnyRole($regionalRoles)) {
            return $query->whereHas('pastor', function ($q) use ($user) {
                $q->where('region_id', $user->region_id);
            });
        }

        // Roles nacionales (pueden ver y editar, pero no crear)
        $nationalRoles = ['Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional', 'Administrador'];
        if ($user->hasAnyRole($nationalRoles)) {
            return $query;
        }

        // Si el usuario no tiene ninguno de los roles anteriores, no ve nada
        return $query->whereNull('id');
    }
}