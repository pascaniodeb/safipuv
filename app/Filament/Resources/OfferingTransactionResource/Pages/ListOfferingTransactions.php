<?php

namespace App\Filament\Resources\OfferingTransactionResource\Pages;

use App\Filament\Resources\OfferingTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfferingTransactions extends ListRecords
{
    protected static string $resource = OfferingTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Registro'),
        ];
    }
}