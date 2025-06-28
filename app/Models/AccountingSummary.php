<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAccountingAccess;
use Carbon\Carbon;
//use Spatie\Activitylog\Traits\LogsActivity;

class AccountingSummary extends Model
{
    use HasFactory, HasAccountingAccess;
    
    protected $fillable = [
        'user_id',
        'accounting_id',
        'accounting_code_id',
        'currency',
        'total_income',
        'total_expense',
        'saldo',
        'month',
        'period_type',
        'period_label',
        'description',
        'region_id',
        'district_id',
        'sector_id',
        'is_closed',
    ];
    
    protected $casts = [
        'total_income'   => 'decimal:2',
        'total_expense'  => 'decimal:2',
        'saldo'          => 'decimal:2',
        'is_closed'      => 'boolean',
    ];
    

    /* ------------------------------------------------------------------ */
    /*  MÉTODO DE GENERACIÓN (ya corregido para total_income / expense)   */
    /* ------------------------------------------------------------------ */

    public static function generateForMonth(string $month, int $accountingId): string
    {
        $user = auth()->user();

        if (! AccountingTransaction::isMonthClosed($month, $accountingId)) {
            throw new \Exception("No se puede generar el resumen porque el mes {$month} aún no ha sido cerrado.");
        }

        if (self::where('month', $month)->where('accounting_id', $accountingId)->exists()) {
            throw new \Exception("Ya existe un resumen para el mes {$month}.");
        }

        $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // Define los IDs correctos para ingresos y egresos
        $idsIngreso = [1, 5]; // ← adapta según tus movimientos reales
        $idsEgreso  = [2, 6];

        $agrupados = DB::table('accounting_transactions')
            ->where('accounting_id', $accountingId)
            ->whereBetween('transaction_date', [$start, $end])
            ->select('currency')
            ->selectRaw('SUM(CASE WHEN movement_id IN ('.implode(',', $idsIngreso).') THEN amount ELSE 0 END) AS total_income')
            ->selectRaw('SUM(CASE WHEN movement_id IN ('.implode(',', $idsEgreso).') THEN amount ELSE 0 END) AS total_expense')
            ->groupBy('currency')
            ->get();

        foreach ($agrupados as $row) {
            $income  = (float) $row->total_income;
            $expense = (float) $row->total_expense;
            $saldo   = $income - $expense;

            self::create([
                'user_id'         => $user->id,
                'accounting_id'   => $accountingId,
                'currency'        => $row->currency,
                'total_income'    => $income,
                'total_expense'   => $expense,
                'saldo'           => $saldo,
                'month'           => $month,
                'period_type'     => 'mensual',
                'period_label'    => $month,
                'description'     => "Resumen contable de {$month}",
                'region_id'       => $user->region_id,
                'district_id'     => $user->district_id,
                'sector_id'       => $user->sector_id,
                'is_closed'       => true,
            ]);
        }

        return "✅ Resumen contable generado correctamente para el mes {$month}.";
    }



    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounting(): BelongsTo
    {
        return $this->belongsTo(Accounting::class);
    }

    public function accountingCode(): BelongsTo
    {
        return $this->belongsTo(AccountingCode::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AccountingTransaction::class, 'accounting_transaction_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}