<?php

namespace App\Filament\Resources\OfferingDistributionResource\Pages;

use App\Filament\Resources\OfferingDistributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfferingDistributions extends ListRecords
{
    protected static string $resource = OfferingDistributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Distribución'),
        ];
    }
}