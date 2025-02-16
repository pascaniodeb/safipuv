<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OfferingItem extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'offering_report_id',
        'offering_category_id',
        'amount_bs',
        'amount_usd',
        'amount_cop',
        'subtotal_bs',
        'total_bs',
        'total_usd',
        'total_cop',
        'total_usd_to_bs',
        'total_cop_to_bs',
        'grand_total_bs',
        'bank_transaction_id',
        'bank_id',
        'transaction_date',
        'transaction_number',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Item de Ofrenda') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($offeringItem) {
            $offeringReport = $offeringItem->offeringReport;

            \Log::info("📌 OfferingItem creado en OfferingReport ID {$offeringReport->id}. Verificando distribución...");

            // Ejecutar la distribución solo si el reporte tiene items
            if ($offeringReport->offeringItems()->count() > 0) {
                \Log::info("✅ OfferingReport ID {$offeringReport->id} ahora tiene items. Ejecutando distributeOfferings...");
                $offeringReport->distributeOfferings();
            } else {
                \Log::warning("⚠ OfferingReport ID {$offeringReport->id} sigue sin items. No se ejecutó distributeOfferings.");
            }
        });
    }

    

    public static function rules($record = null): array
    {
        return [
            'offering_category_id' => [
                'required',
                function ($attribute, $value, $fail) use ($record) {
                    $pastorId = request()->input('pastor_id');
                    $month = request()->input('month');

                    if ($pastorId && $month) {
                        $exists = \App\Models\OfferingItem::whereHas('offeringReport', function ($query) use ($pastorId, $month) {
                                $query->where('pastor_id', $pastorId)
                                    ->where('month', $month);
                            })
                            ->where('offering_category_id', $value)
                            ->when($record, fn($q) => $q->where('id', '!=', $record->id)) // Excluir si está editando
                            ->exists();

                        if ($exists) {
                            $fail('Este tipo de ofrenda ya ha sido registrada para este pastor en el mismo mes.');
                        }
                    }
                },
            ],
        ];
    }



    public function offeringReport()
    {
        return $this->belongsTo(OfferingReport::class);
    }

    public function offeringCategory()
    {
        return $this->belongsTo(OfferingCategory::class);
    }
}