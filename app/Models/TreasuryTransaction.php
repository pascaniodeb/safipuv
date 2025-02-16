<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreasuryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'offering_transaction_id',
        'treasury_id',
        'amount',
        'percentage',
        'month',
        'remarks',
        'subdivision_name',
    ];

    /**
     * Relación con la transacción de ofrenda.
     */
    public function offeringTransaction()
    {
        return $this->belongsTo(OfferingTransaction::class, 'offering_transaction_id');
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class, 'offering_id');
    }


    /**
     * Relación con la tesorería.
     */
    public function treasury()
    {
        return $this->belongsTo(Treasury::class, 'treasury_id');
    }

    /**
     * Scope para filtrar por mes.
     */
    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope para filtrar por tesorería específica.
     */
    public function scopeByTreasury($query, $treasuryId)
    {
        return $query->where('treasury_id', $treasuryId);
    }

    /**
     * Scope para obtener transacciones con subdivisión.
     */
    public function scopeWithSubdivision($query)
    {
        return $query->whereNotNull('subdivision_name');
    }

    /**
     * Formatear el monto en Bs.
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, ',', '.');
    }

    /**
     * Obtener el nombre completo de la tesorería con subdivisión (si aplica).
     */
    public function getFullTreasuryNameAttribute()
    {
        return $this->subdivision_name 
            ? "{$this->treasury->name} - {$this->subdivision_name}" 
            : $this->treasury->name;
    }

    public function sector()
    {
        return $this->hasOneThrough(
            Sector::class, 
            Pastor::class, 
            'id', // Clave en `pastors`
            'id', // Clave en `sectors`
            'offering_transaction_id', // Clave en `treasury_transactions`
            'sector_id' // Clave en `pastors`
        );
    }

}