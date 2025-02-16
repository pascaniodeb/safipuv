<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PastorLevel extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'pastor_levels';

    /**
     * Los atributos que se pueden asignar en masa.
     *
     * @var array
     */
    protected $fillable = [
        'licence_id',
        'name',
        'description',
        'number',
        'anosmin',
        'anosmax',
    ];

    /**
     * Relación con PastorLicence.
     * Un nivel de pastor pertenece a una licencia.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pastorLicence()
    {
        return $this->belongsTo(PastorLicence::class, 'licence_id');
    }
}


