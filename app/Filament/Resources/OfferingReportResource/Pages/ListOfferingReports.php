<?php

namespace App\Filament\Resources\OfferingReportResource\Pages;

use App\Filament\Resources\OfferingReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfferingReports extends ListRecords
{
    protected static string $resource = OfferingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Reporte'),
        ];
    }
}