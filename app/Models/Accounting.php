<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Accounting extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Accounting') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }
    
    protected $fillable = ['name', 'description', 'treasury_id'];

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    public function codes()
    {
        return $this->hasMany(AccountingCode::class);
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\AccountingTransaction::class, 'accounting_id');
    }

}