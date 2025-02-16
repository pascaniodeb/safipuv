<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BankAccount extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'region_id',
        'district_id',
        'sector_id',
        'bank_id',
        'bank_transaction_id',
        'bank_account_type_id',
        'username_id',
        'email',
        'tax_id',
        'business_name',
        'account_number',
        'mobile_payment_number',
        'active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Cuentas Bancarias') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function transaction()
    {
        return $this->belongsTo(BankTransaction::class, 'bank_transaction_id');
    }

    public function accountType()
    {
        return $this->belongsTo(BankAccountType::class, 'bank_account_type_id');
    }

    public function getUserRoleName(): ?string
    {
        return $this->user?->getRoleNames()->first(); // Obtiene el nombre del rol asignado al usuario
    }
}