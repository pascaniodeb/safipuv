<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\PastorLicenceService;
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
        'municipality_id',
        'parish_id',
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

    protected static function booted()
    {
        static::creating(function ($pastorMinistry) {
            if (is_null($pastorMinistry->pastor_licence_id)) {
                $pastorMinistry->pastor_licence_id = \App\Services\PastorAssignmentService::determineLicence(
                    $pastorMinistry->pastor_income_id,
                    $pastorMinistry->pastor_type_id,
                    $pastorMinistry->start_date_ministry
                );
            }
        });
        
        static::updating(function ($pastorMinistry) {
            if (is_null($pastorMinistry->pastor_licence_id)) {
                $pastorMinistry->pastor_licence_id = \App\Services\PastorAssignmentService::determineLicence(
                    $pastorMinistry->pastor_income_id,
                    $pastorMinistry->pastor_type_id,
                    $pastorMinistry->start_date_ministry
                );
            }
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

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function parish()
    {
        return $this->belongsTo(Parish::class);
    }

    public function pastorLicence()
    {
        return $this->belongsTo(\App\Models\PastorLicence::class);
    }

    public function pastorLevel()
    {
        return $this->belongsTo(PastorLevel::class);
    }

    public function income()
    {
        return $this->belongsTo(\App\Models\PastorIncome::class, 'pastor_income_id');
    }

    public function type()
    {
        return $this->belongsTo(\App\Models\PastorType::class, 'pastor_type_id');
    }

    public function licence()
    {
        return $this->belongsTo(\App\Models\PastorLicence::class, 'pastor_licence_id');
    }

    public function level()
    {
        return $this->belongsTo(\App\Models\PastorLevel::class, 'pastor_level_id');
    }

    public function courseType()
    {
        return $this->belongsTo(\App\Models\CourseType::class, 'course_type_id');
    }

    public function positionType()
    {
        return $this->belongsTo(\App\Models\PositionType::class, 'position_type_id');
    }

    public function currentPosition()
    {
        return $this->belongsTo(\App\Models\CurrentPosition::class, 'current_position_id');
    }

    public function church()
    {
        return $this->belongsTo(\App\Models\Church::class, 'church_id');
    }




}