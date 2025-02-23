<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne; // ✅ Importar correctamente
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements HasAvatar
{
    use HasRoles, HasFactory, Notifiable, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'lastname',
        'username',
        'email',
        'profile_photo',
        'password',
        'region_id',
        'district_id',
        'sector_id',
        'nationality_id',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Usuarios') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }


    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function pastor()
    {
        return $this->belongsTo(Pastor::class, 'number_cedula', 'username');
    }


    public function treasury()
    {
        return $this->hasOne(Treasury::class, 'level', 'treasury_level');
    }

    public function getTreasuryLevelAttribute()
    {
        if ($this->hasRole('Tesorero Nacional')) {
            return 'Nacional';
        } elseif ($this->hasRole('Tesorero Regional')) {
            return 'Regional';
        } elseif ($this->hasRole('Tesorero Distrital')) {
            return 'Distrital';
        } elseif ($this->hasRole('Tesorero Sectorial')) {
            return 'Sectorial';
        } elseif ($this->hasRole('Tesorero Local')) {
            return 'Local';
        }

        return null; // Si no tiene ninguno de estos roles
    }



    public function isActive(): bool
    {
        return $this->active;
    }

    public function scopeInRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    public function scopeInDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    public function scopeInSector($query, $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }
    
    public function canAccessFilament(): bool
    {
        // Lista de roles permitidos
        $allowedRoles = [
            //'Administrador',
            'Obispo Presidente',
            'Obispo Vicepresidente',
            'Secretario Nacional',
            'Tesorero Nacional',
            'Contralor Nacional',
            'Inspector Nacional',
            'Directivo Nacional',
            'Superintendente Regional',
            'Secretario Regional',
            'Tesorero Regional',
            'Contralor Regional',
            'Inspector Regional',
            'Directivo Regional',
            'Supervisor Distrital',
            'Presbítero Sectorial',
            'Secretario Sectorial',
            'Tesorero Sectorial',
            'Contralor Sectorial',
            'Directivo Sectorial',
            'Pastor'
        ];

        // Verificar si el usuario tiene alguno de los roles permitidos
        return $this->hasAnyRole($allowedRoles);
    }
}