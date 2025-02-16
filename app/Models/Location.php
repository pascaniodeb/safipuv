<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Relación con bancos.
     */
    public function banks()
    {
        return $this->hasMany(Bank::class);
    }
}