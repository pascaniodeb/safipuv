<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferingTreasurySubdivision extends Model
{
    use HasFactory;

    protected $fillable = ['distribution_id', 'name', 'percentage'];

    public function distribution()
    {
        return $this->belongsTo(OfferingTreasuryDistribution::class, 'distribution_id');
    }
}