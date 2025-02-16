<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = ['location_id', 'bank_code', 'name', 'active'];

    /**
     * Relación con la ubicación (location).
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}