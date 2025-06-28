<?php

namespace App\Filament\Resources\AccountingTransactionResource\Widgets;

use App\Models\AccountingTransaction;
use App\Traits\HasAccountingAccess;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class AccountingTransactionStats extends Widget implements HasForms
{
    use InteractsWithForms;
    use HasAccountingAccess;

    /* ------------------------------------------------------------------ */
    /*  CONFIGURACIÓN                                                     */
    /* ------------------------------------------------------------------ */

    protected static string $view = 'filament.widgets.accounting-transaction-stats';

    public static function canView(): bool
    {
        return true;
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    /* ------------------------------------------------------------------ */
    /*  PROPIEDADES PUBLICAS → visibles en la vista                        */
    /* ------------------------------------------------------------------ */

    public string $ingStr   = '—';
    public string $egrStr   = '—';
    public string $saldoStr = '—';
    public string $estadoMes = 'Sin información';

    /* ------------------------------------------------------------------ */
    /*  INITIAL MOUNT                                                     */
    /* ------------------------------------------------------------------ */

    public function mount(): void
    {
        $acc = $this->getUserAccounting();
        if (! $acc) {
            $this->setSinPermiso();
            return;
        }

        \Log::info('AccountingTransactionStats - Accounting ID: ' . $acc->id);
        \Log::info('AccountingTransactionStats - User: ' . auth()->user()->name);

        /* ① Armar la query base con filtros geográficos */
        $base = AccountingTransaction::query()->where('accounting_id', $acc->id);
        $this->aplicarFiltroGeografico($base);

        /* ② Obtener todos los meses con registros */
        $mesesConRegistros = $this->getMesesConRegistros($base);
        
        if ($mesesConRegistros->isEmpty()) {
            $this->setSinRegistros();
            return;
        }

        \Log::info('AccountingTransactionStats - Meses con registros: ' . $mesesConRegistros->pluck('mes')->join(', '));

        /* ③ Separar meses abiertos y cerrados */
        $mesesAbiertos = [];
        $mesesCerrados = [];
        
        foreach ($mesesConRegistros as $mesData) {
            if ($this->isMesCerrado($base, $mesData->mes)) {
                $mesesCerrados[] = $mesData->mes;
            } else {
                $mesesAbiertos[] = $mesData->mes;
            }
        }

        \Log::info('AccountingTransactionStats - Meses abiertos: ' . implode(', ', $mesesAbiertos));
        \Log::info('AccountingTransactionStats - Meses cerrados: ' . implode(', ', $mesesCerrados));

        /* ④ Calcular totales */
        $this->calcularTotales($base, $mesesAbiertos, $mesesCerrados);
    }

    /* ------------------------------------------------------------------ */
    /*  LÓGICA PRINCIPAL DE CÁLCULO                                      */
    /* ------------------------------------------------------------------ */

    private function calcularTotales($base, array $mesesAbiertos, array $mesesCerrados): void
    {
        // Inicializar totales
        $ingresosTotal = ['VES'=>0,'USD'=>0,'COP'=>0];
        $egresosTotal = ['VES'=>0,'USD'=>0,'COP'=>0];
        $saldoInicial = ['VES'=>0,'USD'=>0,'COP'=>0];

        // 1. Si hay meses cerrados, obtener el saldo final del último mes cerrado
        if (!empty($mesesCerrados)) {
            $ultimoMesCerrado = max($mesesCerrados);
            $saldoInicial = $this->getSaldoFinalMesCerrado($base, $ultimoMesCerrado);
            \Log::info("AccountingTransactionStats - Saldo inicial del último mes cerrado ({$ultimoMesCerrado}): " . json_encode($saldoInicial));
        }

        // 2. Sumar ingresos y egresos de todos los meses abiertos
        foreach ($mesesAbiertos as $mes) {
            $ingMes = $this->getTotalMes($base, $mes, 1, excludeSaldoInicial: true);
            $egrMes = $this->getTotalMes($base, $mes, 2);
            
            // Si es el primer mes y no hay meses cerrados, incluir saldo inicial
            if (empty($mesesCerrados) && $mes === min($mesesAbiertos)) {
                $saldoInicialMes = $this->getTotalMes($base, $mes, 1, onlySaldoInicial: true);
                $saldoInicial = $this->sumarArrays($saldoInicial, $saldoInicialMes);
                \Log::info("AccountingTransactionStats - Saldo inicial del primer mes abierto ({$mes}): " . json_encode($saldoInicialMes));
            }

            $ingresosTotal = $this->sumarArrays($ingresosTotal, $ingMes);
            $egresosTotal = $this->sumarArrays($egresosTotal, $egrMes);

            \Log::info("AccountingTransactionStats - Mes {$mes} - Ingresos: " . json_encode($ingMes) . ", Egresos: " . json_encode($egrMes));
        }

        // 3. Calcular saldo final
        $saldoFinal = $this->combina($saldoInicial, $ingresosTotal, $egresosTotal);

        // 4. Establecer strings para la vista
        $this->ingStr = $this->format($ingresosTotal);
        $this->egrStr = $this->format($egresosTotal);
        $this->saldoStr = $this->format($saldoFinal);

        // 5. Estado del mes
        if (empty($mesesAbiertos)) {
            $this->estadoMes = "Todos los meses cerrados";
        } else {
            $this->estadoMes = "Meses abiertos: " . implode(', ', array_map(function($mes) {
                return Carbon::createFromFormat('Y-m', $mes)->format('M Y');
            }, $mesesAbiertos));
        }

        \Log::info('AccountingTransactionStats - RESUMEN FINAL:');
        \Log::info('  - Saldo inicial: ' . json_encode($saldoInicial));
        \Log::info('  - Ingresos totales: ' . json_encode($ingresosTotal));
        \Log::info('  - Egresos totales: ' . json_encode($egresosTotal));
        \Log::info('  - Saldo final: ' . json_encode($saldoFinal));
    }

    /* ------------------------------------------------------------------ */
    /*  HELPERS PARA OBTENER DATOS                                       */
    /* ------------------------------------------------------------------ */

    private function getMesesConRegistros($base): \Illuminate\Support\Collection
    {
        $baseClonada = clone $base;
        return $baseClonada
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
    }

    private function isMesCerrado($base, string $mes): bool
    {
        [$inicio, $fin] = $this->rangoMes($mes);
        
        $baseClonada = clone $base;
        $totalRegistros = $baseClonada->whereBetween('transaction_date', [$inicio, $fin])->count();
        
        $baseClonada2 = clone $base;
        $registrosCerrados = $baseClonada2
            ->whereBetween('transaction_date', [$inicio, $fin])
            ->where('is_closed', true)
            ->count();
            
        \Log::info("AccountingTransactionStats - Mes {$mes}: {$registrosCerrados}/{$totalRegistros} registros cerrados");
        
        return $totalRegistros > 0 && $totalRegistros === $registrosCerrados;
    }

    private function getTotalMes($base, string $mes, int $movementId, bool $excludeSaldoInicial = false, bool $onlySaldoInicial = false): array
    {
        [$inicio, $fin] = $this->rangoMes($mes);
        
        $query = clone $base;
        $query->whereBetween('transaction_date', [$inicio, $fin])
              ->where('movement_id', $movementId);

        if ($excludeSaldoInicial) {
            $query->whereDoesntHave('accountingCode', fn ($h) => $h->where('code', 'like', 'I-%00'));
        }

        if ($onlySaldoInicial) {
            $query->whereHas('accountingCode', fn ($h) => $h->where('code', 'like', 'I-%00'));
        }

        $tot = $query->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        \Log::info("AccountingTransactionStats - getTotalMes({$mes}, movement={$movementId}) - Raw results: " . json_encode($tot));

        $result = [
            'VES' => $tot['VES'] ?? 0,
            'USD' => $tot['USD'] ?? 0,
            'COP' => $tot['COP'] ?? 0,
        ];
        
        \Log::info("AccountingTransactionStats - getTotalMes({$mes}, movement={$movementId}) - Formatted: " . json_encode($result));

        return $result;
    }

    private function getSaldoFinalMesCerrado($base, string $mes): array
    {
        // Para obtener el saldo final de un mes cerrado, 
        // buscamos el saldo inicial del mes siguiente
        $mesSiguiente = Carbon::createFromFormat('Y-m-d', $mes . '-01')->addMonth()->format('Y-m');
        
        // Si existe saldo inicial en el mes siguiente, ese es nuestro saldo final
        $saldoSiguiente = $this->getTotalMes($base, $mesSiguiente, 1, onlySaldoInicial: true);
        
        // Si no hay saldo inicial en el mes siguiente, calculamos el saldo del mes cerrado
        if (array_sum($saldoSiguiente) == 0) {
            $saldoInicial = $this->getTotalMes($base, $mes, 1, onlySaldoInicial: true);
            $ingresos = $this->getTotalMes($base, $mes, 1, excludeSaldoInicial: true);
            $egresos = $this->getTotalMes($base, $mes, 2);
            $saldoSiguiente = $this->combina($saldoInicial, $ingresos, $egresos);
        }

        \Log::info("AccountingTransactionStats - Saldo final mes cerrado {$mes}: " . json_encode($saldoSiguiente));
        return $saldoSiguiente;
    }

    /* ------------------------------------------------------------------ */
    /*  HELPERS MATEMÁTICOS                                              */
    /* ------------------------------------------------------------------ */

    private function sumarArrays(array $a, array $b): array
    {
        return [
            'VES' => $a['VES'] + $b['VES'],
            'USD' => $a['USD'] + $b['USD'],
            'COP' => $a['COP'] + $b['COP'],
        ];
    }

    private function combina(array $saldoIn, array $ing, array $egr): array
    {
        return [
            'VES' => $saldoIn['VES'] + $ing['VES'] - $egr['VES'],
            'USD' => $saldoIn['USD'] + $ing['USD'] - $egr['USD'],
            'COP' => $saldoIn['COP'] + $ing['COP'] - $egr['COP'],
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  HELPERS EXISTENTES                                                */
    /* ------------------------------------------------------------------ */

    private function aplicarFiltroGeografico($q): void
    {
        $u = auth()->user();
        
        \Log::info('AccountingTransactionStats - Usuario: ' . $u->name . ' (ID: ' . $u->id . ')');
        \Log::info('AccountingTransactionStats - Roles: ' . $u->roles->pluck('name')->join(', '));
        \Log::info('AccountingTransactionStats - Geografía: sector=' . ($u->sector_id ?? 'NULL') . ', district=' . ($u->district_id ?? 'NULL') . ', region=' . ($u->region_id ?? 'NULL'));

        if ($u->hasRole(['Presbítero Sectorial', 'Tesorero Sectorial', 'Contralor Sectorial'])) {
            if ($u->sector_id) {
                $q->where('sector_id', $u->sector_id);
                \Log::info('AccountingTransactionStats - APLICANDO filtro sectorial: ' . $u->sector_id);
            }
        } elseif ($u->hasRole('Supervisor Distrital')) {
            if ($u->district_id) {
                $q->where('district_id', $u->district_id);
                \Log::info('AccountingTransactionStats - APLICANDO filtro distrital: ' . $u->district_id);
            }
        } elseif ($u->hasRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional'])) {
            if ($u->region_id) {
                $q->where('region_id', $u->region_id);
                \Log::info('AccountingTransactionStats - APLICANDO filtro regional: ' . $u->region_id);
            }
        } else {
            \Log::info('AccountingTransactionStats - Usuario Nacional - SIN filtros geográficos');
        }
    }

    private function rangoMes(string $ym): array
    {
        $inicio = Carbon::createFromFormat('Y-m-d', $ym . '-01')->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();
        return [$inicio, $fin];
    }

    public function getFormSchema(): array
    {
        return [];
    }

    private function format(array $d): string
    {
        $lines = [];
        
        // Solo mostrar divisas que tengan valor diferente de cero
        if ($d['VES'] != 0) {
            $lines[] = "Bs. " . number_format($d['VES'], 2, ',', '.');
        }
        
        if ($d['USD'] != 0) {
            $lines[] = "Usd: " . number_format($d['USD'], 2, ',', '.');
        }
        
        if ($d['COP'] != 0) {
            $lines[] = "Cop: " . number_format($d['COP'], 2, ',', '.');
        }
        
        // Si no hay valores, mostrar guión
        if (empty($lines)) {
            return "—";
        }
        
        return implode("\n", $lines);
    }

    private function setSinPermiso(): void
    {
        $this->ingStr = $this->egrStr = $this->saldoStr = 'Sin Contabilidad';
        $this->estadoMes = 'Sin permisos';
    }

    private function setSinRegistros(): void
    {
        $this->ingStr = $this->egrStr = $this->saldoStr = 'Sin registros';
        $this->estadoMes = 'No hay datos';
    }
}