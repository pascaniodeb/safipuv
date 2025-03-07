<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Services\OfferingReportFilterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OfferingReport extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'month',
        'treasury_id',
        'number_report',
        'pastor_id',
        'pastor_name',
        'pastor_type_id',
        'church_id',
        'region_id',
        'district_id',
        'sector_id',
        'amount_bs',
        'amount_usd',
        'amount_cop',
        'subtotal_bs',
        'usd_rate',
        'cop_rate',
        'total_bs',
        'total_usd',
        'total_cop',
        'total_usd_to_bs',
        'total_cop_to_bs',
        'grand_total_bs',
        'remarks',
        'status', // ✅ Asegurar que está aquí
    ];

    public static function generateReportNumber()
    {
        return DB::transaction(function () {
            // Bloquea la fila para evitar concurrencia
            $sequence = DB::table('report_sequences')->where('type', 'report')->lockForUpdate()->first();

            // Incrementa el número de reporte
            $newNumber = $sequence->last_number + 1;

            // No actualizamos la base de datos aquí aún, solo generamos el número
            return 'REP-' . str_pad($newNumber, 9, '0', STR_PAD_LEFT);
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('accessControl', function (Builder $query) {
            OfferingReportFilterService::applyFilters($query);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Reporte Mensual') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($offeringReport) {
            if (!$offeringReport->treasury_id) {
                // 🔹 Determinar el nivel de tesorería en función del sector, distrito o región
                $level = null;
        
                if ($offeringReport->sector_id) {
                    $level = 'Sectorial'; // Para sectorial
                } elseif ($offeringReport->district_id) {
                    $level = 'Distrital'; // Para distrital
                } elseif ($offeringReport->region_id) {
                    $level = 'Regional'; // Para regional
                }
        
                // 🔹 Si no se definió el nivel, lanzar error
                if (!$level) {
                    \Log::error("⚠ Error: No se pudo determinar el nivel de tesorería en OfferingReport.");
                    throw new \Exception("No se encontró una tesorería válida para este reporte.");
                }
        
                // 🔹 Buscar la tesorería correspondiente según el nivel
                $offeringReport->treasury_id = Treasury::where('level', $level)->first()?->id;
        
                // 🔹 Si no se encontró una tesorería, registrar error
                if (!$offeringReport->treasury_id) {
                    \Log::error("⚠ Error: No se encontró una tesorería para level={$level}");
                    throw new \Exception("No se encontró una tesorería correspondiente al nivel {$level}");
                }
            }
            // ✅ Solo establecer 'pendiente' si el campo está vacío
            if (!$offeringReport->status) {
                $offeringReport->status = 'pendiente';
            }
            // Si el número de reporte no ha sido asignado (seguridad extra)
            if (empty($report->number_report)) {
                // Ahora sí actualizamos la base de datos cuando el registro se guarda
                DB::transaction(function () {
                    DB::table('report_sequences')
                        ->where('type', 'report')
                        ->update(['last_number' => DB::raw('last_number + 1')]);
                });

                // Asignamos el número de reporte final
                $report->number_report = self::generateReportNumber();
            }
        });
        

        // ✅ Ya NO es necesario ejecutar distributeOfferings() aquí
        static::created(function ($offeringReport) {
            \Log::info("📌 OfferingReport ID {$offeringReport->id} guardado. Verificando offeringItems...");

            // Solo logueamos si hay o no items, pero NO ejecutamos distributeOfferings() aquí
            if ($offeringReport->offeringItems()->count() > 0) {
                \Log::info("✅ OfferingReport ID {$offeringReport->id} ya tiene items.");
            } else {
                \Log::warning("⚠ OfferingReport ID {$offeringReport->id} no tiene items todavía.");
            }
        });

        // Si se actualiza, recalcular la distribución
        static::updated(function ($offeringReport) {
            $offeringReport->recalculateDistribution();
        });

        // Si se borra, eliminar distribuciones asociadas
        static::deleted(function ($offeringReport) {
            TreasuryAllocation::where('offering_report_id', $offeringReport->id)->delete();
        });
    }


    /**
     * Distribuye automáticamente las ofrendas entre las tesorerías.
     */
    public function distributeOfferings()
    {
        Log::info("🚀 Iniciando distribución para OfferingReport ID: " . $this->id);

        // ✅ Verificar si el mes está vacío y asignarlo automáticamente
        if (empty($this->month)) {
            $this->update(['month' => date('Y-m')]);
            Log::info("📆 Se asignó automáticamente 'month' con el valor: " . $this->month);
        }

        // Obtener las categorías de ofrendas incluidas en este reporte
        $offeringCategories = $this->offeringItems->pluck('offering_category_id')->unique();
        Log::info("🎯 Categorías detectadas: " . json_encode($offeringCategories));

        // Obtener las reglas de distribución para esas categorías
        $distributions = OfferingDistribution::whereIn('offering_category_id', $offeringCategories)->get();
        if ($distributions->isEmpty()) {
            Log::warning("⚠ No hay reglas de distribución para estas categorías: " . json_encode($offeringCategories));
            return;
        }

        foreach ($distributions as $distribution) {
            // Obtener el total de esta categoría
            $categoryTotal = $this->offeringItems()
                ->where('offering_category_id', $distribution->offering_category_id)
                ->sum('subtotal_bs');

            Log::info("📊 Total de la categoría {$distribution->offering_category_id}: {$categoryTotal} Bs");

            // Calcular la cantidad a distribuir
            $distributedAmount = ($categoryTotal * $distribution->percentage) / 100;

            Log::info("💰 Monto a distribuir ({$distribution->percentage}%): {$distributedAmount} Bs para Tesorería ID {$distribution->target_treasury_id}");

            if (!$distribution->targetTreasury) {
                Log::error('⛔ Tesorería destino no encontrada para distribución.');
                continue;
            }

            // ✅ Verificar si ya existe una asignación para esta combinación
            $existingAllocation = TreasuryAllocation::where([
                'offering_report_id' => $this->id,
                'treasury_id' => $distribution->target_treasury_id,
                'offering_category_id' => $distribution->offering_category_id,
                'month' => $this->month,
            ])->first();

            if ($existingAllocation) {
                // ✅ Si existe, actualizar el monto y el porcentaje
                $existingAllocation->update([
                    'amount' => $distributedAmount,
                    'percentage' => $distribution->percentage,
                    'remarks' => "Actualización automática de distribución a " . strtoupper($distribution->targetTreasury->name),
                ]);
                Log::info("♻️ Actualización de asignación para Tesorería: {$distribution->targetTreasury->name}");
            } else {
                // ✅ Si no existe, crear una nueva asignación
                TreasuryAllocation::create([
                    'offering_report_id' => $this->id,
                    'treasury_id' => $distribution->target_treasury_id,
                    'offering_category_id' => $distribution->offering_category_id,
                    'amount' => $distributedAmount,
                    'percentage' => $distribution->percentage,
                    'month' => $this->month ?? date('Y-m'), // ✅ Se asegura que siempre tenga un valor
                    'remarks' => "Distribución automática a " . strtoupper($distribution->targetTreasury->name),
                ]);
                Log::info("✅ Nueva asignación creada para la Tesorería: {$distribution->targetTreasury->name}");
            }
        }
    }






    /**
     * Manejo de subdivisiones dentro de una tesorería.
     */
    private function handleSubdivisions($distribution, $totalAmount)
    {
        Log::info("📌 Iniciando subdivisión para distribución ID: " . $distribution->id);

        $distribution->load('subdivisions'); // 🔥 Asegurar que las subdivisiones se cargan antes de usarlas

        foreach ($distribution->subdivisions as $subdivision) {
            Log::info("🛠 Procesando subdivisión: " . json_encode($subdivision));

            if ($subdivision->percentage <= 0) {
                Log::warning("⚠ Subdivisión ignorada: {$subdivision->subdivision_name} (Porcentaje: {$subdivision->percentage}%)");
                continue;
            }

            // Calcular el monto basado en el porcentaje
            $subAmount = ($totalAmount * $subdivision->percentage) / 100;

            TreasuryAllocation::create([
                'offering_report_id' => $this->id,
                'treasury_id' => $distribution->target_treasury_id,
                'offering_category_id' => $distribution->offering_category_id,
                'amount' => $subAmount,
                'percentage' => $subdivision->percentage,
                'month' => $this->month,
                'remarks' => "Subdivisión: " . strtoupper($subdivision->subdivision_name),
            ]);

            Log::info("✅ Subdivisión creada: {$subdivision->subdivision_name} ({$subAmount} Bs)");
        }
    }



    /**
     * Recalcula la distribución si el reporte es actualizado.
     */
    public function recalculateDistribution()
    {
        TreasuryAllocation::where('offering_report_id', $this->id)->delete();
        $this->distributeOfferings();
    }



    /**
     * Relaciones con otras tablas
     */

    public function pastor()
    {
        return $this->belongsTo(Pastor::class, 'pastor_id');
    }

    public function pastorType()
    {
        return $this->belongsTo(PastorType::class, 'pastor_type_id');
    }

    public function church()
    {
        return $this->belongsTo(Church::class, 'church_id');
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    public function offeringItems()
    {
        return $this->hasMany(OfferingItem::class);
    }

    public function treasuryAllocations()
    {
        return $this->hasMany(TreasuryAllocation::class);
    }
}