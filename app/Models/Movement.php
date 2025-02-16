<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    use HasFactory;

    protected $fillable = ['type'];

    public function accountingCodes()
    {
        return $this->hasMany(AccountingCode::class);
    }
}