<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreasuryAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'offering_report_id',
        'treasury_id',
        'offering_category_id',
        'amount',
        'percentage',
        'month',
        'remarks',
    ];

    protected $casts = [
        'remarks' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    

    protected $appends = ['total_amount', 'average_percentage'];

    public function getTotalAmountAttribute()
    {
        return $this->attributes['total_amount'] ?? 0; // Asegura que no sea null
    }

    public function getAveragePercentageAttribute()
    {
        return $this->attributes['average_percentage'] ?? 0; // Asegura que no sea null
    }

    public function offeringReport()
    {
        return $this->belongsTo(OfferingReport::class, 'offering_report_id');
    }

    public function treasury()
    {
        return $this->belongsTo(Treasury::class, 'treasury_id');
    }

    public function offeringCategory()
    {
        return $this->belongsTo(OfferingCategory::class, 'offering_category_id');
    }
}