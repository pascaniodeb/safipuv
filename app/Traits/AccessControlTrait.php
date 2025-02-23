<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Services\AccessControlService;

trait AccessControlTrait
{
    public static function scopeAccessControlQuery(Builder $query): Builder
    {
        return AccessControlService::applyFilters($query);
    }
}