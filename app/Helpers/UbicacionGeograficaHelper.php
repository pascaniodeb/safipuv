<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use App\Models\Region;
use App\Models\District;
use App\Models\Sector;

class UbicacionGeograficaHelper
{
    public static function userIsInRoleGroup(string $group): bool
    {
        $user = Auth::user();

        $rolesByGroup = [
            'nationalRoles' => [
                'Administrador',
                'Tesorero Nacional',
                'Secretario Nacional',
            ],
            'regionalRoles' => [
                'Supervisor Regional',
                'Tesorero Regional',
                'Secretario Regional',
            ],
            'districtRoles' => [
                'Supervisor Distrital',
                'Tesorero Distrital',
                'Secretario Distrital',
            ],
            'sectorRoles' => [
                'Supervisor Sectorial',
                'Tesorero Sectorial',
                'Secretario Sectorial',
            ],
        ];

        return $user && $user->hasAnyRole($rolesByGroup[$group] ?? []);
    }

    public static function defaultValue(string $filter): mixed
    {
        $user = Auth::user();

        return match ($filter) {
            'region_id' => self::userIsInRoleGroup('regionalRoles') || self::userIsInRoleGroup('districtRoles') || self::userIsInRoleGroup('sectorRoles')
                ? $user->region_id : null,

            'district_id' => self::userIsInRoleGroup('districtRoles') || self::userIsInRoleGroup('sectorRoles')
                ? $user->district_id : null,

            'sector_id' => self::userIsInRoleGroup('sectorRoles')
                ? $user->sector_id : null,

            default => null,
        };
    }

    public static function regionOptions(): array
    {
        $user = Auth::user();

        if (self::userIsInRoleGroup('nationalRoles')) {
            return Region::pluck('name', 'id')->toArray();
        }

        if ($user->region_id) {
            return Region::where('id', $user->region_id)->pluck('name', 'id')->toArray();
        }

        return [];
    }

    public static function districtOptions(?int $regionId = null): array
    {
        $user = Auth::user();
        $regionIdToUse = $regionId ?? self::defaultValue('region_id');

        $query = District::query();

        if (self::userIsInRoleGroup('nationalRoles')) {
            $query->when($regionIdToUse, fn ($q) => $q->where('region_id', $regionIdToUse));
        } elseif (self::userIsInRoleGroup('regionalRoles') || self::userIsInRoleGroup('districtRoles') || self::userIsInRoleGroup('sectorRoles')) {
            if ($user->region_id) {
                $query->where('region_id', $user->region_id);
            } else {
                return [];
            }
        }

        if (self::userIsInRoleGroup('districtRoles') || self::userIsInRoleGroup('sectorRoles')) {
            if ($user->district_id) {
                $query->where('id', $user->district_id);
            } else {
                return [];
            }
        }

        return $query->pluck('name', 'id')->toArray();
    }

    public static function sectorOptions(?int $selectedDistrictId = null): array
    {
        $user = Auth::user();
        $query = Sector::query();

        $selectedDistrictId = $selectedDistrictId ? (int) $selectedDistrictId : null;
        $userDistrictId = $user->district_id ?? null;

        if ($selectedDistrictId) {
            if (self::userIsInRoleGroup('nationalRoles')) {
                $query->where('district_id', $selectedDistrictId);
            } elseif (self::userIsInRoleGroup('regionalRoles')) {
                $regionalDistricts = District::where('region_id', $user->region_id)->pluck('id')->toArray();
                if (in_array($selectedDistrictId, $regionalDistricts, true)) {
                    $query->where('district_id', $selectedDistrictId);
                } else {
                    return [];
                }
            } elseif (self::userIsInRoleGroup('districtRoles') || self::userIsInRoleGroup('sectorRoles')) {
                if ($selectedDistrictId !== $userDistrictId) {
                    return [];
                }
                $query->where('district_id', $userDistrictId);
            }
        } else {
            if (self::userIsInRoleGroup('regionalRoles')) {
                if ($user->region_id) {
                    $regionalDistricts = District::where('region_id', $user->region_id)->pluck('id')->toArray();
                    $query->whereIn('district_id', $regionalDistricts);
                } else {
                    return [];
                }
            } elseif (self::userIsInRoleGroup('districtRoles') || self::userIsInRoleGroup('sectorRoles')) {
                if ($userDistrictId) {
                    $query->where('district_id', $userDistrictId);
                    if (self::userIsInRoleGroup('sectorRoles') && $user->sector_id) {
                        $query->where('id', $user->sector_id);
                    }
                } else {
                    return [];
                }
            }
            // Nacional sin filtro → no aplicar restricción por distrito
        }

        return $query->pluck('name', 'id')->toArray();
    }
}