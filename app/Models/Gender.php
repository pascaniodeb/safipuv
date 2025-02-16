<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    use HasFactory;

    // Campos que se pueden asignar masivamente
    protected $fillable = ['name'];

    // Relación con CurrentPosition
    public function currentPositions()
    {
        return $this->hasMany(CurrentPosition::class);
    }
}
