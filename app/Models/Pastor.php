<?php

namespace App\Models;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Pastor extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Los atributos que se pueden asignar en masa.
     */
    protected $fillable = [
        'region_id',
        'district_id',
        'sector_id',
        'state_id',
        'city_id',
        'name',
        'lastname',
        'gender_id',
        'nationality_id',
        'number_cedula',
        'blood_type_id',
        'birthdate',
        'birthplace',
        'academic_level_id',
        'career',
        'phone_mobile',
        'phone_house',
        'email',
        'photo_pastor',
        'marital_status_id',
        'baptism_date',
        'who_baptized',
        'start_date_ministry',
        'housing_type_id',
        'address',
        'social_security',
        'housing_policy',
        'other_work',
        'how_work',
        'other_studies',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'birthdate' => 'date',
        'baptism_date' => 'date',
        'start_date_ministry' => 'date',
        'social_security' => 'boolean',
        'housing_policy' => 'boolean',
        'other_work' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Pastores') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }


    /**
     * Relaciones con otras tablas.
     */
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

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function bloodType()
    {
        return $this->belongsTo(BloodType::class);
    }

    public function academicLevel()
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function housingType()
    {
        return $this->belongsTo(HousingType::class);
    }

    public function families()
    {
        return $this->hasMany(Family::class);
    }

    public function pastorMinistries()
    {
        return $this->hasMany(PastorMinistry::class);
    }

    public function ministries()
    {
        return $this->hasMany(PastorMinistry::class);
    }

    // Relación con PastorMinistry
    public function ministry()
    {
        return $this->hasOne(PastorMinistry::class, 'pastor_id');
    }

    //public function currentPosition()
    //{
        //return $this->belongsTo(Position::class, 'current_position_id');
    //}

    public function pastorMinistry()
    {
        return $this->hasOne(\App\Models\PastorMinistry::class, 'pastor_id');
    }

    public function church()
    {
        return $this->belongsTo(Church::class);
    }

    public function pastorType()
    {
        return $this->belongsTo(PastorType::class);
    }

    // Relación con PastorType
    public function type()
    {
        return $this->belongsTo(PastorType::class, 'pastor_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Asegúrate de que la clave foránea sea correcta
    }

    // En el modelo Pastor
    public static function forUser($user)
    {
        if (!$user) {
            return static::query()->whereRaw('0 = 1'); // No devuelve nada si no hay usuario
        }

        if ($user->hasRole('Administrador')) {
            return static::query(); // Los administradores pueden ver todos los pastores
        }

        if ($user->hasRole('Tesorero Nacional')) {
            return static::query(); // Los tesoreros nacionales pueden ver todos los pastores
        }

        if ($user->hasRole('Tesorero Regional')) {
            return static::query()->whereHas('region', function ($query) use ($user) {
                $query->where('id', $user->region_id); // Filtra pastores por la región del usuario
            });
        }

        if ($user->hasRole('Tesorero Sectorial')) {
            return static::query()->where('sector_id', $user->sector_id); // Filtra pastores por el sector del usuario
        }

        return static::query()->whereRaw('0 = 1'); // Por defecto, no devuelve nada
    }



    public function churches()
    {
        return $this->belongsToMany(Church::class, 'church_pastor')
                    ->withPivot('pastor_type_id')
                    ->withTimestamps();
    }

    public function offeringTransactions()
    {
        return $this->hasMany(OfferingTransaction::class, 'pastor_id');
    }

    
    

}