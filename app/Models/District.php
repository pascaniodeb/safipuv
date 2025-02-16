<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class District extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'region_id',
        'number',
        'active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Distritos') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    /**
     * Obtener la región a la que pertenece el distrito.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function sectors()
    {
        return $this->hasMany(Sector::class);
    }
}