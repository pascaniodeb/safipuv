<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attached extends Model
{
    use HasFactory;

    protected $table = 'attached';

    protected $fillable = [
        'region_id',
        'state_id'
    ];

    /**
     * Obtener la región a la que pertenece el registro.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Obtener el estado al que pertenece el registro.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
