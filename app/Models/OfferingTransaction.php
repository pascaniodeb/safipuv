<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TreasuryTransaction;
use App\Models\OfferingTreasuryDistribution;

class OfferingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'offering_id',
        'amount_bs',
        'amount_usd',
        'amount_cop',
        'subtotal_bs',
        'pastor_id',
        'month',
        'usd_rate',
        'cop_rate',
        'total_bs',
        'total_usd_to_bs',
        'total_cop_to_bs',
        'grand_total_bs',
    ];

    // 📌 Relación con el Tipo de Ofrenda
    public function offering()
    {
        return $this->belongsTo(Offering::class, 'offering_id');
    }

    // Relación: Un informe tiene muchos ítems de ofrenda
    //public function offeringItems()
    //{
        //return $this->hasMany(OfferingItem::class);
    //}

    public function offeringItems()
    {
        return $this->hasMany(OfferingTransaction::class);
    }


    public function pastor()
    {
        return $this->belongsTo(User::class, 'pastor_id');
    }

    public function treasuryTransactions()
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Crear automáticamente las distribuciones de tesorería al crear una ofrenda
        static::created(function ($offeringTransaction) {
            $offeringTransaction->distributeFunds();
        });

        // Actualizar automáticamente las distribuciones de tesorería si la ofrenda se actualiza
        static::updated(function ($offeringTransaction) {
            $offeringTransaction->updateDistributions();
        });

        // Eliminar distribuciones si se borra la ofrenda
        static::deleted(function ($offeringTransaction) {
            TreasuryTransaction::where('offering_transaction_id', $offeringTransaction->id)->delete();
        });
    }

    /**
     * Generar las distribuciones de tesorería automáticamente.
     */
    public function distributeFunds()
    {
        $distributions = OfferingTreasuryDistribution::where('offering_id', $this->offering_id)->get();

        foreach ($distributions as $distribution) {
            // Calcular el monto correspondiente a esta tesorería
            $distributedAmount = ($this->subtotal_bs * $distribution->percentage) / 100;

            TreasuryTransaction::create([
                'offering_transaction_id' => $this->id,
                'treasury_id' => $distribution->treasury_id,
                'offering_id' => $this->offering->id, // Usando la propiedad $this->offering
                'amount' => $distributedAmount,
                'percentage' => $distribution->percentage,
                'month' => $this->month,
                'remarks' => "Descuento automático para " . strtoupper($distribution->treasury->name),
            ]);

            // Si esta distribución tiene subdivisión, procesarla
            if ($distribution->has_subdivision) {
                $this->handleSubdivisions($distribution, $distributedAmount);
            }
        }
    }

    /**
     * Manejar la subdivisión de la ofrenda si es necesario.
     */
    private function handleRegionalSubdivisions($distribution, $totalAmount)
    {
        // Verificar si la distribución es para la Tesorería Regional de "El Poder del Uno"
        if ($distribution->offering_id == 2 && $distribution->treasury->name == 'regional') {
            // El totalAmount aquí es el 17.98% recibido. Ahora lo tratamos como 100%.
            
            // Calcular los nuevos montos basados en la subdivisión:
            $amountForNucleo = ($totalAmount * 57.45) / 100; // 57.45% del 100% recibido
            $amountForFondo = ($totalAmount * 42.55) / 100; // 42.55% del 100% recibido

            // Crear transacción para Núcleo
            TreasuryTransaction::create([
                'offering_transaction_id' => $this->id,
                'treasury_id' => $distribution->treasury_id,
                'amount' => $amountForNucleo,
                'percentage' => 57.45, // Esto ya representa el % del total recibido
                'month' => $this->month,
                'subdivision_name' => 'NÚCLEO',
                'remarks' => "Subdivisión dentro de Tesorería Regional - Núcleo (57.45%)",
            ]);

            // Crear transacción para Fondo
            TreasuryTransaction::create([
                'offering_transaction_id' => $this->id,
                'treasury_id' => $distribution->treasury_id,
                'amount' => $amountForFondo,
                'percentage' => 42.55, // Esto ya representa el % del total recibido
                'month' => $this->month,
                'subdivision_name' => 'FONDO',
                'remarks' => "Subdivisión dentro de Tesorería Regional - Fondo (42.55%)",
            ]);
        }
    }


    /**
     * Si la ofrenda es actualizada, recalcular las distribuciones.
     */
    public function updateDistributions()
    {
        TreasuryTransaction::where('offering_transaction_id', $this->id)->delete();
        $this->distributeFunds();
    }
}