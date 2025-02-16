<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Carnet extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'carnets';

    /**
     * Atributos asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'pastor_id',
        'pastor_licence_id',
        'pastor_type_id',
        'is_active',
        'file_path',
        'generated_by',
        'custom_data',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'custom_data' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Licencias') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    /**
     * Relación con el modelo Pastor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pastor()
    {
        return $this->belongsTo(Pastor::class);
    }

    /**
     * Relación con el modelo PastorLicence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pastorLicence()
    {
        return $this->belongsTo(PastorLicence::class);
    }

    /**
     * Relación con el modelo PastorType.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pastorType()
    {
        return $this->belongsTo(PastorType::class);
    }
}