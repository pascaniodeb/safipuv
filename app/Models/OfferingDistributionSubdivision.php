<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferingDistributionSubdivision extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_id',
        'subdivision_name',
        'percentage'];

    // Relación con OfferingDistribution
    public function distribution()
    {
        return $this->belongsTo(OfferingDistribution::class, 'distribution_id'); // ✅ Especifica la clave foránea
    }
}