<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AccountingTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'accounting_id',
        'accounting_code_id',
        'movement_id',
        'amount',
        'currency',
        'description',
        'month',
        'user_id',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'month' => 'string',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // 🔹 Registra todos los cambios en el modelo
            ->logOnlyDirty() // 🔹 Solo registra los cambios realizados, no repite valores iguales
            ->useLogName('Contabilidad') // 🔹 Nombre del log en la base de datos
            ->dontSubmitEmptyLogs(); // 🔹 Evita guardar logs vacíos
    }


    /**
     * Relación con la contabilidad.
     */
    public function accounting()
    {
        return $this->belongsTo(Accounting::class, 'accounting_id');
    }

    public function treasury()
    {
        return $this->hasOneThrough(
            Treasury::class,
            Accounting::class,
            'id', // Clave foránea en Accounting
            'id', // Clave foránea en Treasury
            'accounting_id', // Clave local en AccountingTransaction
            'treasury_id' // Clave local en Accounting
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            $user = auth()->user();
            $transaction->region_id = $user->region_id;
            $transaction->district_id = $user->district_id;
            $transaction->sector_id = $user->sector_id;
        });
    }

    public static function closeMonth(string $month, int $accountingId)
    {
        $nextMonth = Carbon::createFromFormat('Y-m', $month)->addMonth()->format('Y-m');
        $currencies = ['VES', 'USD', 'COP']; // Divisas soportadas
        $userId = auth()->id();

        foreach ($currencies as $currency) {
            // Verificar si ya existe un saldo inicial en el siguiente mes para esta divisa
            $exists = self::where('accounting_id', $accountingId)
                ->where('month', $nextMonth)
                ->where('currency', $currency)
                ->where('description', "Saldo inicial de {$month}")
                ->exists();

            if ($exists) {
                continue; // Saltar si ya fue cerrado en esta divisa
            }

            // Obtener IDs de movimientos (Ingreso y Egreso)
            $incomeMovementId = Movement::where('type', 'Ingreso')->value('id');
            $expenseMovementId = Movement::where('type', 'Egreso')->value('id');

            // Calcular ingresos y egresos en la divisa específica
            $totalIncome = self::where('accounting_id', $accountingId)
                ->where('month', $month)
                ->where('currency', $currency)
                ->where('movement_id', $incomeMovementId)
                ->sum('amount');

            $totalExpense = self::where('accounting_id', $accountingId)
                ->where('month', $month)
                ->where('currency', $currency)
                ->where('movement_id', $expenseMovementId)
                ->sum('amount');

            $closingBalance = $totalIncome - $totalExpense;

            // Solo registrar si hay saldo disponible
            if ($closingBalance != 0) {
                self::create([
                    'accounting_id' => $accountingId,
                    'accounting_code_id' => AccountingCode::where('description', 'Saldo Inicial')->value('id'),
                    'movement_id' => $incomeMovementId, // Ingreso
                    'currency' => $currency,
                    'amount' => $closingBalance,
                    'description' => "Saldo inicial de {$month}",
                    'month' => $nextMonth,
                    'user_id' => $userId,
                ]);
            }
        }

        return "✅ Cierre de {$month} completado para todas las divisas.";
    }
    

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
    
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
    
    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }
    

    /**
     * Relación con el código contable.
     */
    public function accountingCode(): BelongsTo
    {
        return $this->belongsTo(AccountingCode::class);
    }

    /**
     * Relación con el tipo de movimiento (ingreso o egreso).
     */
    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    /**
     * Relación con el usuario que registró la transacción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene la URL del recibo si existe.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path ? asset('storage/' . $this->receipt_path) : null;
    }
}