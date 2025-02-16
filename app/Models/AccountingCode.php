<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class AccountingCode extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['accounting_id', 'role_id', 'movement_id', 'code', 'description', 'active'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Códigos Contables') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function movement()
    {
        return $this->belongsTo(Movement::class);
    }
}