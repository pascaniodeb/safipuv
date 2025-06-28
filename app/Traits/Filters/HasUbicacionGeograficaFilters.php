<?php

namespace App\Traits\Filters;

use Filament\Tables\Filters\SelectFilter;
use App\Helpers\UbicacionGeograficaHelper;

trait HasUbicacionGeograficaFilters
{
    public static function getUbicacionGeograficaFilters(): array
    {
        return [
            SelectFilter::make('region_id')
                ->label('Región')
                ->native(false)
                ->options(fn () => UbicacionGeograficaHelper::regionOptions())
                ->default(fn () => UbicacionGeograficaHelper::defaultValue('region_id'))
                ->placeholder('Todas las regiones'),

            SelectFilter::make('district_id')
                ->label('Distrito')
                ->native(false)
                ->searchable()
                ->options(function () {
                    $filters = request()->input('tableFilters', []);
                    $regionId = isset($filters['region_id']) ? (int) $filters['region_id'] : null;
                    return UbicacionGeograficaHelper::districtOptions($regionId);
                })
                ->default(fn () => UbicacionGeograficaHelper::defaultValue('district_id'))
                ->placeholder('Todos los distritos'),

            SelectFilter::make('sector_id')
                ->label('Sector')
                ->native(false)
                ->searchable()
                ->options(function () {
                    $filters = request()->input('tableFilters', []);
                    $districtId = isset($filters['district_id']) ? (int) $filters['district_id'] : null;
                    return UbicacionGeograficaHelper::sectorOptions($districtId);
                })
                ->default(fn () => UbicacionGeograficaHelper::defaultValue('sector_id'))
                ->placeholder('Todos los sectores'),
        ];
    }
}