<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PastorLicence extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'pastor_licences';

    /**
     * Los atributos que se pueden asignar en masa.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relación con PastorLevel.
     * Una licencia puede estar asociada a múltiples niveles de pastor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pastorLevels()
    {
        return $this->hasMany(PastorLevel::class, 'licence_id');
    }

}