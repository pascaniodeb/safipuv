<?php

namespace App\Filament\Resources\CarnetResource\Pages;

use App\Filament\Resources\CarnetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCarnet extends EditRecord
{
    protected static string $resource = CarnetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
