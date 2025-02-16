<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PastorType extends Model
{
    use HasFactory;

    protected $fillable = ['name']; // Asegúrate de que el campo 'name' esté definido en la base de datos

    public function pastors()
    {
        return $this->hasMany(Pastor::class);
    }
}
