<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AccessControlService;
use App\Traits\PastorAccessTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;


class Church extends Model
{
    use SoftDeletes, HasFactory, LogsActivity, PastorAccessTrait;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'churches';

    /**
     * Los atributos que se pueden asignar en masa.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'date_opening',
        'pastor_founding',
        'code_church',
        'type_infrastructure',
        'legalized',
        'legal_entity_number',
        'number_rif',
        'region_id',
        'district_id',
        'sector_id',
        'state_id',
        'city_id',
        'municipality_id',
        'parish_id',
        'address',
        'pastor_current',
        'number_cedula',
        'current_position_id',
        'email',
        'phone',
        'adults',
        'children',
        'baptized',
        'to_baptize',
        'holy_spirit',
        'groups_cells',
        'centers_preaching',
        'members',
        'category_church_id',
        'directive_local',
        'pastor_attach',
        'name_pastor_attach',
        'pastor_assistant',
        'name_pastor_assistant',
        'co_pastor',
        'professionals',
        'name_professionals',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Iglesias') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($church) {
            if (!$church->code_church) {
                $month = str_pad(now()->parse($church->date_opening)->format('m'), 2, '0', STR_PAD_LEFT);
                $year = now()->parse($church->date_opening)->format('Y');
                $lastCode = self::latest('id')->value('code_church');
                $lastIncrement = $lastCode ? intval(substr($lastCode, -4)) : 0;
                $nextIncrement = str_pad($lastIncrement + 1, 4, '0', STR_PAD_LEFT);
                $church->code_church = "M{$month}A{$year}C{$nextIncrement}";
            }
        });
        static::creating(function ($church) {
            if (!$church->category_church_id) {
                $totalMembers = $church->adults + $church->children;

                // Calcula la categoría basada en la cantidad de miembros
                $category = \App\Models\CategoryChurch::where('membersmin', '<=', $totalMembers)
                    ->where('membersmax', '>=', $totalMembers)
                    ->first();

                $church->category_church_id = $category?->id;
            }
        });
    }

    protected $casts = [
        'name_professionals' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope('accessControl', function (Builder $query) {
            AccessControlService::applyFilters($query);
        });
    }
    
    
    /**
     * Relaciones
     */

    // Relación con Región
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    // Relación con Distrito
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    // Relación con Sector
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    // Relación con Estado
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    // Relación con Ciudad
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // Relación con Municipio
    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    // Relación con Parroquia
    public function parish()
    {
        return $this->belongsTo(Parish::class);
    }
    
    // Relación con Posición Actual
    public function currentPosition()
    {
        return $this->belongsTo(CurrentPosition::class, 'current_position_id');
    }

    // Relación con Categoría de Iglesia
    public function categoryChurch()
    {
        return $this->belongsTo(CategoryChurch::class, 'category_church_id');
    }

    public function ministries()
    {
        return $this->hasMany(PastorMinistry::class);
    }

    public function pastorMinistries()
    {
        return $this->hasMany(PastorMinistry::class, 'church_id', 'id');
    }

    // En App\Models\Church.php
    public function pastor()
    {
        return $this->hasOne(\App\Models\PastorMinistry::class, 'church_id')
            ->where('active', true);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\ChurchCategory::class, 'category_church_id');
    }



    public function titularPastor()
    {
        return $this->hasOne(PastorMinistry::class)
            ->where('pastor_type_id', 1); // Filtrar por pastor titular
    }

    public function adjunctPastor()
    {
        return $this->hasOne(PastorMinistry::class)
            ->where('pastor_type_id', 2); // Filtrar por pastor Adjunto
    }

    public function assistantPastors()
    {
        return $this->ministries()->where('pastor_type_id', 3)->latest()->take(2)->get();
    }

    public function currentPastor()
    {
        return $this->hasOne(Pastor::class, 'church_id');
    }

    public function offeringReports()
    {
        return $this->hasMany(\App\Models\OfferingReport::class);
    }
    




    




}