<?php

namespace App\Filament\Resources\PastorLevelResource\Pages;

use App\Filament\Resources\PastorLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPastorLevels extends ListRecords
{
    protected static string $resource = PastorLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Nivel'),
        ];
    }
}
