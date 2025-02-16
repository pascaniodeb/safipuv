<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'active', 'has_subdivision'];

    public function distributions()
    {
        return $this->hasMany(OfferingTreasuryDistribution::class);
    }

}