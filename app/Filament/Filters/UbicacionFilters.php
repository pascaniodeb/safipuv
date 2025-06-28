<?php

namespace App\Filament\Filters;

use Filament\Tables\Filters\SelectFilter;
use App\Models\Region;
use App\Models\District;
use App\Models\Sector;

class UbicacionFilters
{
    public static function regionDistritoSector(): array
    {
        return [
            SelectFilter::make('region_id')
                ->label('Región')
                ->options(Region::pluck('name', 'id')->toArray())
                ->placeholder('Todas las regiones'),

            SelectFilter::make('district_id')
                ->label('Distrito')
                ->options(function () {
                    $regionId = request()->input('tableFilters.region_id');
            
                    return \App\Models\District::when($regionId, fn ($query) =>
                        $query->where('region_id', $regionId)
                    )->pluck('name', 'id')->toArray();
                })
                ->placeholder('Todos los distritos'),
            

            SelectFilter::make('sector_id')
                ->label('Sector')
                ->options(fn (callable $get) =>
                    Sector::query()
                        ->when($get('district_id'), fn ($query) =>
                            $query->where('district_id', $get('district_id'))
                        )
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->placeholder('Todos los sectores'),
        ];
    }
}