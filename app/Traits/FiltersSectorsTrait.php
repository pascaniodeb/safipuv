<?php

namespace App\Traits;

use App\Models\Sector;
use Illuminate\Support\Facades\Auth;

trait FiltersSectorsTrait
{
    /**
     * Retorna un array [ 'id_del_sector' => 'Nombre del sector', ... ]
     * con los sectores filtrados según el rol/datos del usuario actual.
     */
    // Dentro del trait, un método estático:
    public static function getSectorsForCurrentUserStatic(): array
    {
        $user = auth()->user();

        $sectorsQuery = Sector::query()->orderBy('name');

        if ($user->hasRole('Supervisor Distrital')) {
            $sectorsQuery->where('district_id', $user->district_id);
            
        } elseif ($user->hasRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional'])) {
            $sectorsQuery->whereHas('district', function ($districtQuery) use ($user) {
                $districtQuery->where('region_id', $user->region_id);
            });
        
        }
        // Nacional -> no filtra

        return $sectorsQuery->pluck('name', 'id')->toArray();
    }

}