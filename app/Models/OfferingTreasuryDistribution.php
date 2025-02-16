<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferingTreasuryDistribution extends Model
{
    use HasFactory;

    protected $fillable = ['offering_id', 'treasury_id', 'percentage', 'has_subdivision'];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    public function subdivisions()
    {
        return $this->hasMany(OfferingTreasurySubdivision::class, 'distribution_id');
    }

}