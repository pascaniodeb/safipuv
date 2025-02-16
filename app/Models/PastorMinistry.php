<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PastorMinistry extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'pastor_id',
        'code_pastor',
        'start_date_ministry',
        'pastor_income_id',
        'pastor_type_id',
        'active',
        'church_id',
        'code_church',
        'region_id',
        'district_id',
        'sector_id',
        'state_id',
        'city_id',
        'address',
        'abisop',
        'iblc',
        'course_type_id',
        'pastor_licence_id',
        'pastor_level_id',
        'position_type_id',
        'current_position_id',
        'appointment',
        'promotion_year',
        'promotion_number',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Información Ministerial') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ministry) {
            // Asegura que 'start_date_ministry' se copie del pastor relacionado al crear un registro
            $ministry->start_date_ministry = $ministry->pastor->start_date_ministry;
        });
    }


    // Relación inversa con Pastor
    public function pastor()
    {
        return $this->belongsTo(Pastor::class, 'pastor_id');
    }

    public function pastorType()
    {
        return $this->belongsTo(PastorType::class);
    }

    public function pastorIncome()
    {
        return $this->belongsTo(PastorIncome::class);
    }

    public function church()
    {
        return $this->belongsTo(Church::class);
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

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function courseType()
    {
        return $this->belongsTo(CourseType::class);
    }

    public function pastorLicence()
    {
        return $this->belongsTo(PastorLicence::class);
    }

    public function positionType()
    {
        return $this->belongsTo(PositionType::class);
    }

    public function currentPosition()
    {
        return $this->belongsTo(CurrentPosition::class);
    }

    public function pastorLevel()
    {
        return $this->belongsTo(PastorLevel::class);
    }

    public function pastorLevelVip()
    {
        return $this->belongsTo(PastorLevelVip::class);
    }



}