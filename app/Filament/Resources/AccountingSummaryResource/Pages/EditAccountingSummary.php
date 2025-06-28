<?php

namespace App\Filament\Resources\AccountingSummaryResource\Pages;

use App\Filament\Resources\AccountingSummaryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountingSummary extends EditRecord
{
    protected static string $resource = AccountingSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
