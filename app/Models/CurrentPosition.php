<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentPosition extends Model
{
    use HasFactory;

    // Campos que se pueden asignar masivamente
    protected $fillable = ['name', 'description', 'position_type_id', 'gender_id'];

    // Relación con PositionType
    public function positionType()
    {
        return $this->belongsTo(PositionType::class);
    }

    // Relación con Gender
    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }
    // Relación con Pastor   
    public function pastors()
    {
        return $this->hasMany(Pastor::class, 'current_position_id');
    }


}