<?php

namespace App\Filament\Resources\TreasuryAllocationResource\Pages;

use App\Filament\Resources\TreasuryAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTreasuryAllocations extends ListRecords
{
    protected static string $resource = TreasuryAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
