<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class OfferingCategory extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'description', 'active'];

    public function offeringDistributions()
    {
        return $this->hasMany(OfferingDistribution::class);
    }
}