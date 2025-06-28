<?php

namespace App\Filament\Resources\ActivitylogResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ActivitylogResource;

class ViewActivitylog extends ViewRecord
{
    public static function getResource(): string
    {
        return ActivitylogResource::class;
    }
}