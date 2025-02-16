<?php

namespace App\Filament\Resources\OfferingCategoryResource\Pages;

use App\Filament\Resources\OfferingCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfferingCategories extends ListRecords
{
    protected static string $resource = OfferingCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Ofrenda'),
        ];
    }
}