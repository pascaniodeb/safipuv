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

    public function getMontoDistribuidoEn(string $currency, \App\Models\User $user): float
    {
        $percentage = ($this->percentage ?? 0) / 100;
        if (!$percentage) return 0;

        $query = \DB::table('offering_items')
            ->join('offering_reports', 'offering_items.offering_report_id', '=', 'offering_reports.id')
            ->join('exchange_rates', function($join) use ($currency) {
                $join->on('exchange_rates.sector_id', '=', 'offering_reports.sector_id')
                    ->on('exchange_rates.month', '=', 'offering_reports.month')
                    ->where('exchange_rates.operation', '=', 'D')
                    ->where('exchange_rates.currency', '=', $currency);
            })
            ->where('offering_reports.month', $this->month)
            ->where('offering_items.offering_category_id', $this->offering_category_id)
            ->where('offering_reports.status', 'aprobado')
            ->where('exchange_rates.rate_to_bs', '>', 0);

        // Aplicar filtros por usuario
        if ($user->hasRole('Tesorero Sectorial')) {
            $query->where('offering_reports.sector_id', $user->sector_id);
        } elseif ($user->hasRole('Supervisor Distrital')) {
            $query->where('offering_reports.district_id', $user->district_id);
        } elseif ($user->hasRole('Tesorero Regional')) {
            $query->where('offering_reports.region_id', $user->region_id);
        } elseif (!$user->hasRole('Tesorero Nacional')) {
            return 0;
        }

        // Aplicar fórmula de conversión
        $formula = match ($currency) {
            'USD' => "(offering_items.subtotal_bs / exchange_rates.rate_to_bs)",
            'COP' => "(offering_items.subtotal_bs * exchange_rates.rate_to_bs)",
            default => null,
        };

        if (!$formula) return 0;

        return $query->sum(\DB::raw("{$formula} * {$percentage}"));
    }


}