<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PastorLevelVip extends Model
{
    use HasFactory;

    // Si no usas timestamps, puedes deshabilitarlos:
    public $timestamps = true;

    // Nombre de la tabla si no sigue la convención Laravel
    protected $table = 'pastor_level_vip';

    // Campos que se pueden rellenar masivamente
    protected $fillable = ['name'];
}