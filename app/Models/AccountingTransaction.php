<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAccountingAccess;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AccountingTransaction extends Model
{
    use HasFactory, LogsActivity, HasAccountingAccess;

    protected $fillable = [
        'accounting_id',
        'accounting_code_id',
        'movement_id',
        'amount',
        'currency',
        'description',
        'transaction_date',
        'user_id',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
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

    protected static function booted()
    {
        static::saving(function ($record) {
            if ($record->is_closed) {
                throw new \Exception('No se pueden guardar registros en un mes cerrado.');
            }
        });

        static::deleting(function ($record) {
            if ($record->is_closed) {
                throw new \Exception('No se pueden eliminar registros en un mes cerrado.');
            }
        });
    }



    public static function closeMonth(string $month, int $accountingId)
    {
        $nextMonth = Carbon::createFromFormat('Y-m', $month)->addMonth();
        $currencies = ['VES', 'USD', 'COP'];
        $user = auth()->user();
        $userId = $user->id;

        // Determinar el código contable según la contabilidad
        $codeMap = [
            2 => 'I-100', // Sectorial
            3 => 'I-200', // Distrital
            4 => 'I-300', // Regional
            6 => 'I-500', // Nacional
        ];

        $code = $codeMap[$accountingId] ?? null;

        if (!$code) {
            throw new \Exception("No se ha definido un código contable para la contabilidad ID {$accountingId}");
        }

        $accountingCodeId = AccountingCode::where('code', $code)->value('id');

        if (!$accountingCodeId) {
            throw new \Exception("No se encontró el código contable '{$code}' en la base de datos.");
        }

        // Obtener los IDs de tipo de movimiento
        $incomeMovementId = Movement::where('type', 'Ingreso')->value('id');
        $expenseMovementId = Movement::where('type', 'Egreso')->value('id');

        foreach ($currencies as $currency) {
            $exists = self::where('accounting_id', $accountingId)
                ->whereDate('transaction_date', $nextMonth->copy()->startOfMonth())
                ->where('currency', $currency)
                ->where('description', "Saldo inicial de {$month}")
                ->exists();

            if ($exists) {
                continue;
            }

            $totalIncome = self::where('accounting_id', $accountingId)
                ->whereMonth('transaction_date', Carbon::parse($month)->month)
                ->whereYear('transaction_date', Carbon::parse($month)->year)
                ->where('currency', $currency)
                ->where('movement_id', $incomeMovementId)
                ->sum('amount');

            $totalExpense = self::where('accounting_id', $accountingId)
                ->whereMonth('transaction_date', Carbon::parse($month)->month)
                ->whereYear('transaction_date', Carbon::parse($month)->year)
                ->where('currency', $currency)
                ->where('movement_id', $expenseMovementId)
                ->sum('amount');

            $saldo = $totalIncome - $totalExpense;

            if ($saldo != 0) {
                self::create([
                    'accounting_id' => $accountingId,
                    'accounting_code_id' => $accountingCodeId,
                    'movement_id' => $incomeMovementId,
                    'currency' => $currency,
                    'amount' => $saldo,
                    'description' => "Saldo inicial de {$month}",
                    'transaction_date' => $nextMonth->copy()->startOfMonth(),
                    'user_id' => $userId,
                    'region_id' => $user->region_id,
                    'district_id' => $user->district_id,
                    'sector_id' => $user->sector_id,
                ]);
            }
        }

        // Cerrar el mes actual
        self::where('accounting_id', $accountingId)
            ->whereMonth('transaction_date', Carbon::parse($month)->month)
            ->whereYear('transaction_date', Carbon::parse($month)->year)
            ->update(['is_closed' => true]);

        return "✅ El mes {$month} ha sido cerrado correctamente con los saldos iniciales registrados.";
    }


    public static function isMonthClosed(string $month, int $accountingId): bool
    {
        return self::where('accounting_id', $accountingId)
            ->whereMonth('transaction_date', '=', Carbon::createFromFormat('Y-m', $month)->month)
            ->whereYear('transaction_date', '=', Carbon::createFromFormat('Y-m', $month)->year)
            ->where('is_closed', true)
            ->exists();
    }

    // En tu modelo AccountingTransaction (o helper)
    public static function getMesAbierto(): ?string
    {
        return self::where('is_closed', false)
            ->orderByDesc('transaction_date')
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as mes")
            ->value('mes');
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