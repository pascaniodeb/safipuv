<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferingDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'offering_category_id',
        'source_treasury_id',
        'target_treasury_id',
        'percentage'];

    public function offeringCategory()
    {
        return $this->belongsTo(OfferingCategory::class);
    }

    public function sourceTreasury()
    {
        return $this->belongsTo(Treasury::class, 'source_treasury_id');
    }

    public function targetTreasury()
    {
        return $this->belongsTo(Treasury::class, 'target_treasury_id');
    }

    // Relación con OfferingDistributionSubdivision
    public function subdivisions()
    {
        return $this->hasMany(OfferingDistributionSubdivision::class, 'distribution_id'); // ✅ Especifica la clave foránea
    }


}