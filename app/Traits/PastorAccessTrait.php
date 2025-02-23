<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Services\PastorAccessService;

trait PastorAccessTrait
{
    protected static function bootPastorAccessTrait()
    {
        static::addGlobalScope('accessControl', function (Builder $query) {
            PastorAccessService::filterPastorsQuery($query);
        });
    }
}