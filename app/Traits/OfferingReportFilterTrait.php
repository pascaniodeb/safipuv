<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Services\OfferingReportFilterService;
use App\Models\OfferingReport;

trait OfferingReportFilterTrait
{
    public static function getFilteredQuery(): Builder
    {
        return OfferingReportFilterService::applyFilters(OfferingReport::query()); // ✅ Llamar al modelo
    }
}