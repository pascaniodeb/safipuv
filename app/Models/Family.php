<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Family extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Los atributos que se pueden asignar en masa.
     */
    protected $fillable = [
        'pastor_id',
        'relation_id',
        'gender_id',
        'name',
        'lastname',
        'nationality_id',
        'blood_type_id',
        'number_cedula',
        'birthdate',
        'birthplace',
        'marital_status_id',
        'academic_level_id',
        'career',
        'phone_mobile',
        'phone_house',
        'email',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'birthdate' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Familia Pastoral') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    /**
     * Relación con el modelo `Pastor`.
     */
    public function pastor()
    {
        return $this->belongsTo(Pastor::class);
    }


    /**
     * Relación con el modelo `Relation`.
     */
    public function relation()
    {
        return $this->belongsTo(Relation::class);
    }

    /**
     * Relación con el modelo `Gender`.
     */
    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * Relación con el modelo `Nationality`.
     */
    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    /**
     * Relación con el modelo `BloodType`.
     */
    public function bloodType()
    {
        return $this->belongsTo(BloodType::class);
    }

    /**
     * Relación con el modelo `MaritalStatus`.
     */
    public function maritalStatus()
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    /**
     * Relación con el modelo `AcademicLevel`.
     */
    public function academicLevel()
    {
        return $this->belongsTo(AcademicLevel::class);
    }


}