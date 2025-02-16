<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryChurch extends Model
{
    protected $fillable = ['name', 'description', 'membersmin', 'membersmax'];

    /**
     * Encuentra la categoría para un rango de miembros.
     */
    public static function findCategoryByMembers(int $members): ?self
    {
        return self::where('membersmin', '<=', $members)
            ->where('membersmax', '>=', $members)
            ->first();
    }
}
