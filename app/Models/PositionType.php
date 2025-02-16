<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PositionType extends Model
{
    public function currentPositions()
    {
        return $this->hasMany(CurrentPosition::class);
    }

}