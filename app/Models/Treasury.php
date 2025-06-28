<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treasury extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    /**
     * Relación con OfferingTreasuryDistribution.
     */
    public function distributions()
    {
        return $this->hasMany(OfferingTreasuryDistribution::class);
    }

    public function accounting()
    {
        return $this->hasOne(Accounting::class);
    }


}