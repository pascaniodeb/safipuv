<?php

namespace App\Filament\Resources\OfferingDistributionSubdivisionResource\Pages;

use App\Filament\Resources\OfferingDistributionSubdivisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfferingDistributionSubdivisions extends ListRecords
{
    protected static string $resource = OfferingDistributionSubdivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Sub-Deducción'),
        ];
    }
}