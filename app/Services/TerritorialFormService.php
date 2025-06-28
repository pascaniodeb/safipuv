<?php

namespace App\Services;

use App\Models\District;
use App\Models\Region;
use App\Models\Sector;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;

class TerritorialFormService
{
    /**
     * Obtiene los componentes de formulario para Región, Distrito y Sector
     * con las restricciones basadas en el rol del usuario autenticado
     */
    public static function getTerritorialComponents(): array
    {
        $user = Auth::user();
        $userLevel = self::getUserTerritorialLevel($user);
        
        return [
            self::getRegionSelect($user, $userLevel),
            self::getDistrictSelect($user, $userLevel),
            self::getSectorSelect($user, $userLevel),
        ];
    }

    /**
     * Determina el nivel territorial del usuario
     */
    private static function getUserTerritorialLevel(User $user): string
    {
        if ($user->hasAnyRole(['Administrador', 'Secretario Nacional', 'Tesorero Nacional', 'Obispo Presidente'])) {
            return 'nacional';
        }
    

        if ($user->hasAnyRole(['Superintendente Regional', 'Secretario Regional', 'Tesorero Regional'])) {
            return 'regional';
        }
        
        if ($user->hasRole('Supervisor Distrital')) {
            return 'distrital';
        }
        
        if ($user->hasAnyRole(['Presbítero Sectorial', 'Secretario Sectorial', 'Tesorero Sectorial', 'Pastor'])) {
            return 'sectorial';
        }
        
        return 'nacional'; // Por defecto
    }

    /**
     * Genera el select de Región
     */
    private static function getRegionSelect(User $user, string $userLevel): Select
    {
        return Select::make('region_id')
            ->label('Región')
            ->relationship('region', 'name')
            ->required()
            ->reactive()
            ->native(false)
            ->afterStateUpdated(function (callable $set) {
                $set('district_id', null);
                $set('sector_id', null);
            })
            ->disabled($userLevel !== 'nacional')
            ->default(function () use ($user, $userLevel) {
                if ($userLevel === 'nacional') {
                    return null;
                }
                return $user->region_id ?? null;
            })
            ->dehydrated();
    }

    /**
     * Genera el select de Distrito
     */
    private static function getDistrictSelect(User $user, string $userLevel): Select
    {
        return Select::make('district_id')
            ->label('Distrito')
            ->options(function (callable $get) use ($user, $userLevel) {
                $regionId = $get('region_id');
                
                // Si no hay región seleccionada y el usuario no es nacional, usar su región
                if (!$regionId && $userLevel !== 'nacional') {
                    $regionId = $user->region_id;
                }
                
                if (!$regionId) {
                    return [];
                }
                
                return District::where('region_id', $regionId)->pluck('name', 'id');
            })
            ->required()
            ->reactive()
            ->native(false)
            ->afterStateUpdated(fn (callable $set) => $set('sector_id', null))
            ->disabled(function (callable $get) use ($userLevel) {
                if ($userLevel === 'sectorial' || $userLevel === 'distrital') {
                    return true;
                }
                
                if ($userLevel === 'regional') {
                    return !$get('region_id');
                }
                
                return !$get('region_id'); // Nacional
            })
            ->default(function () use ($user, $userLevel) {
                if ($userLevel === 'nacional' || $userLevel === 'regional') {
                    return null;
                }
                return $user->district_id ?? null;
            })
            ->dehydrated();
    }

    /**
     * Genera el select de Sector
     */
    private static function getSectorSelect(User $user, string $userLevel): Select
    {
        return Select::make('sector_id')
            ->label('Sector')
            ->options(function (callable $get) use ($user, $userLevel) {
                $districtId = $get('district_id');
                
                // Si no hay distrito seleccionado y el usuario no es nacional/regional, usar su distrito
                if (!$districtId && in_array($userLevel, ['distrital', 'sectorial'])) {
                    $districtId = $user->district_id;
                }
                
                if (!$districtId) {
                    return [];
                }
                
                return Sector::where('district_id', $districtId)->pluck('name', 'id');
            })
            ->required()
            ->native(false)
            ->disabled(function (callable $get) use ($userLevel) {
                if ($userLevel === 'sectorial') {
                    return true;
                }
                
                return !$get('district_id');
            })
            ->default(function () use ($user, $userLevel) {
                if ($userLevel !== 'sectorial') {
                    return null;
                }
                return $user->sector_id ?? null;
            })
            ->dehydrated();
    }

    /**
     * Obtiene los valores por defecto para inicializar el formulario
     * basado en el usuario autenticado
     */
    public static function getDefaultTerritorialValues(): array
    {
        $user = Auth::user();
        $userLevel = self::getUserTerritorialLevel($user);
        
        $defaults = [];
        
        if ($userLevel !== 'nacional') {
            $defaults['region_id'] = $user->region_id;
        }
        
        if (in_array($userLevel, ['distrital', 'sectorial'])) {
            $defaults['district_id'] = $user->district_id;
        }
        
        if ($userLevel === 'sectorial') {
            $defaults['sector_id'] = $user->sector_id;
        }
        
        return $defaults;
    }

    /**
     * Valida si el usuario puede acceder a una región específica
     */
    public static function canAccessRegion(User $user, int $regionId): bool
    {
        $userLevel = self::getUserTerritorialLevel($user);
        
        if ($userLevel === 'nacional') {
            return true;
        }
        
        return $user->region_id === $regionId;
    }

    /**
     * Valida si el usuario puede acceder a un distrito específico
     */
    public static function canAccessDistrict(User $user, int $districtId): bool
    {
        $userLevel = self::getUserTerritorialLevel($user);
        
        if ($userLevel === 'nacional') {
            return true;
        }
        
        if ($userLevel === 'regional') {
            $district = District::find($districtId);
            return $district && $district->region_id === $user->region_id;
        }
        
        return $user->district_id === $districtId;
    }

    /**
     * Valida si el usuario puede acceder a un sector específico
     */
    public static function canAccessSector(User $user, int $sectorId): bool
    {
        $userLevel = self::getUserTerritorialLevel($user);
        
        if ($userLevel === 'nacional') {
            return true;
        }
        
        $sector = Sector::find($sectorId);
        if (!$sector) {
            return false;
        }
        
        if ($userLevel === 'regional') {
            return $sector->district->region_id === $user->region_id;
        }
        
        if ($userLevel === 'distrital') {
            return $sector->district_id === $user->district_id;
        }
        
        return $user->sector_id === $sectorId;
    }
}