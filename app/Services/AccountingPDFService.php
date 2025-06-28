<?php

namespace App\Services;

use App\Models\AccountingTransaction;
use App\Models\AccountingSummary;
use App\Models\AccountingCode;
use App\Models\Accounting;
use App\Traits\HasAccountingAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class AccountingPDFService
{
    use HasAccountingAccess;

    /**
     * Obtener opciones de meses que tienen registros para el usuario
     */
    public function getMonthOptions(): array
    {
        $user = auth()->user();
        if (!$user) return [];

        $accounting = $this->getUserAccounting();
        if (!$accounting) return [];

        // Obtener meses que tienen registros accesibles para este usuario
        $query = AccountingTransaction::where('accounting_id', $accounting->id);

        // Aplicar filtros geográficos según el nivel del usuario
        $this->applyGeographicFilters($query, $user);

        $months = $query
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month")
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->take(24) // Últimos 24 meses
            ->mapWithKeys(function ($month) {
                $date = Carbon::createFromFormat('Y-m', $month);
                return [$month => $date->translatedFormat('F Y')];
            })
            ->toArray();

        return $months;
    }

    /**
     * Obtener contabilidades disponibles para el usuario (solo la que tiene permisos)
     */
    public function getAvailableAccountings(int $userId): array
    {
        $accounting = $this->getUserAccounting();
        
        if (!$accounting) {
            return [];
        }

        $label = $accounting->name;
        if ($accounting->treasury) {
            $label .= ' - ' . $accounting->treasury->name;
        }

        return [$accounting->id => $label];
    }

    /**
     * Obtener la contabilidad por defecto para el usuario
     */
    public function getDefaultAccounting(int $userId): ?int
    {
        $accounting = $this->getUserAccounting();
        return $accounting?->id;
    }

    /**
     * Método de debug para verificar permisos del usuario
     */
    private function debugUserPermissions($user): void
    {
        \Log::info("=== DEBUG USER PERMISSIONS ===");
        \Log::info("Usuario: {$user->name} (ID: {$user->id})");
        \Log::info("Roles: " . $user->roles->pluck('name')->implode(', '));
        \Log::info("Sector ID: " . ($user->sector_id ?? 'null'));
        \Log::info("District ID: " . ($user->district_id ?? 'null'));
        \Log::info("Region ID: " . ($user->region_id ?? 'null'));
        
        $accounting = $this->getUserAccounting();
        \Log::info("Contabilidad accesible: " . ($accounting ? $accounting->id . ' - ' . $accounting->name : 'NINGUNA'));
        
        $nivelInfo = $this->getUserTerritorialLevel();
        \Log::info("Nivel territorial: " . json_encode($nivelInfo));
        \Log::info("=== FIN DEBUG ===");
    }

    /**
     * Generar PDF del resumen contable
     */
    public function generatePDF(array $data, int $userId)
    {
        $user = auth()->user();
        if (!$user) {
            throw new \Exception('Usuario no autenticado');
        }

        // Debug de permisos
        $this->debugUserPermissions($user);

        $accounting = $this->getUserAccounting();
        if (!$accounting) {
            throw new \Exception('No se encontró contabilidad asociada al usuario');
        }

        // Validar que el accounting_id coincida
        if ($accounting->id != $data['accounting_id']) {
            throw new \Exception('No tiene permisos para acceder a esa contabilidad');
        }

        // Calcular fechas según el período
        $fechas = $this->calculatePeriodDates($data['period'], $data['month_year'] ?? null);

        // Determinar el nivel del usuario usando el método del servicio
        $nivelInfo = $this->getUserTerritorialLevel();

        // Si el método del trait está disponible, usarlo como respaldo
        // (El método ya está implementado en esta clase, así que no es necesario llamar a parent)

        // Obtener datos para el PDF (incluye registros de TODOS los usuarios del mismo nivel territorial)
        $ingresos = $this->getMovementsByType($accounting->id, 1, $fechas['inicio'], $fechas['fin'], $user);
        $egresos = $this->getMovementsByType($accounting->id, 2, $fechas['inicio'], $fechas['fin'], $user);

        // Calcular totales
        $totalesIngresos = $this->calculateTotals($ingresos);
        $totalesEgresos = $this->calculateTotals($egresos);
        $saldos = $this->calculateBalances($totalesIngresos, $totalesEgresos);

        // Formatear período
        $periodoFormateado = $this->formatPeriod($data['period'], $fechas['inicio'], $fechas['fin'], $data['month_year'] ?? null);

        // Formatear nombre del usuario
        $nombreUsuario = $this->formatUserName($user->name, $user->lastname ?? '');

        // Obtener alcance del reporte
        $alcance = $this->getReportScope($nivelInfo['level']);

        // Datos para la vista
        $pdfData = [
            'titulo' => 'RESUMEN CONTABLE ' . strtoupper($nivelInfo['level']), // Agregado para la vista
            'title' => 'RESUMEN CONTABLE ' . strtoupper($nivelInfo['level']),
            'nivel' => strtoupper($nivelInfo['level']),
            'nivel_texto' => $this->getNivelTexto($nivelInfo['level']),
            'periodo' => $periodoFormateado,
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i:s'),
            'usuario' => $nombreUsuario,
            'userLevel' => $nivelInfo['display'],
            'contabilidad' => $nivelInfo['name'],
            'accountingName' => $accounting->name,
            'treasuryName' => $accounting->treasury ? $accounting->treasury->name : '',
            'alcance' => $alcance,
            'scope' => $alcance,
            'dateRange' => $fechas['inicio']->format('d/m/Y') . ' - ' . $fechas['fin']->format('d/m/Y'),
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'incomes' => $ingresos, // Alias para compatibilidad con la vista
            'expenses' => $egresos, // Alias para compatibilidad con la vista
            'totales_ingresos' => $totalesIngresos,
            'totales_egresos' => $totalesEgresos,
            'saldos' => $saldos,
            'generatedAt' => Carbon::now()->format('d/m/Y H:i:s'),
            'userName' => $nombreUsuario,
        ];

        // Debug: verificar datos antes de generar PDF
        \Log::info("=== DEBUG PDF DATA ===");
        \Log::info("Datos para el PDF: " . json_encode([
            'titulo' => $pdfData['titulo'],
            'nivel' => $pdfData['nivel'],
            'usuario' => $pdfData['usuario'],
            'contabilidad' => $pdfData['contabilidad'],
            'periodo' => $pdfData['periodo'],
            'ingresos_count' => count($pdfData['ingresos']),
            'egresos_count' => count($pdfData['egresos'])
        ]));

        // Verificar si la vista existe
        $vistaPath = 'pdfs.resumen-contable';
        if (!view()->exists($vistaPath)) {
            \Log::error("Vista no encontrada: {$vistaPath}");
            \Log::info("Intentando rutas alternativas...");
            
            $alternativas = [
                'resumen-contable',
                'pdfs/resumen-contable',
                'accounting-summary-pdf'
            ];
            
            foreach ($alternativas as $alternativa) {
                if (view()->exists($alternativa)) {
                    \Log::info("Vista encontrada en: {$alternativa}");
                    $vistaPath = $alternativa;
                    break;
                }
            }
            
            if (!view()->exists($vistaPath)) {
                throw new \Exception("No se pudo encontrar ninguna vista válida para el PDF. Verifique que existe: resources/views/pdfs/resumen-contable.blade.php");
            }
        }

        // Generar PDF con la vista correcta (verificar ruta)
        $pdf = Pdf::loadView($vistaPath, $pdfData);
        $pdf->setPaper('letter', 'portrait');

        // Nombre del archivo
        $filename = 'resumen_contable_' . strtolower($nivelInfo['level']) . '_' . 
                   $this->sanitizeFilename($periodoFormateado) . '_' .
                   Carbon::now()->format('Y_m_d_H_i_s') . '.pdf';

        // Retornar respuesta de descarga
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Calcular fechas según el período seleccionado
     */
    private function calculatePeriodDates(string $period, ?string $monthYear = null): array
    {
        $now = Carbon::now();

        return match($period) {
            'mes' => [
                'inicio' => Carbon::createFromFormat('Y-m', $monthYear)->startOfMonth(),
                'fin' => Carbon::createFromFormat('Y-m', $monthYear)->endOfMonth(),
            ],
            'ultimo_trimestre' => [
                'inicio' => $now->copy()->subMonths(3)->startOfMonth(),
                'fin' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'ultimo_semestre' => [
                'inicio' => $now->copy()->subMonths(6)->startOfMonth(),
                'fin' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'ultimo_año' => [
                'inicio' => $now->copy()->subYear()->startOfMonth(),
                'fin' => $now->copy()->subMonth()->endOfMonth(),
            ],
            default => [
                'inicio' => $now->copy()->subMonth()->startOfMonth(),
                'fin' => $now->copy()->subMonth()->endOfMonth(),
            ],
        };
    }

    /**
     * Obtener movimientos por tipo (ingresos o egresos) respetando permisos territoriales
     */
    private function getMovementsByType(int $accountingId, int $movementId, $fechaInicio, $fechaFin, $user): array
    {
        $query = AccountingTransaction::with('accountingCode')
            ->where('accounting_id', $accountingId)
            ->where('movement_id', $movementId)
            ->whereBetween('transaction_date', [$fechaInicio, $fechaFin]);

        // Para saldos iniciales, solo incluir los del primer día del período
        if ($movementId == 1) { // Solo para ingresos
            $query->where(function ($q) use ($fechaInicio) {
                $q->whereDoesntHave('accountingCode', fn ($h) => $h->where('code', 'like', 'I-%00'))
                  ->orWhere(function ($subQ) use ($fechaInicio) {
                      // Solo saldos iniciales del primer día del período
                      $subQ->whereHas('accountingCode', fn ($h) => $h->where('code', 'like', 'I-%00'))
                           ->whereDate('transaction_date', $fechaInicio->format('Y-m-d'));
                  });
            });
        }

        // IMPORTANTE: Aplicar filtros geográficos para incluir registros de TODOS los usuarios del mismo nivel
        $this->applyGeographicFilters($query, $user);

        $transacciones = $query->get();

        \Log::info("getMovementsByType - Movement {$movementId} - Período: {$fechaInicio} a {$fechaFin}");
        \Log::info("getMovementsByType - Movement {$movementId} - Transacciones encontradas: " . $transacciones->count());

        // Agrupar por código contable y sumar por divisa
        $movimientos = [];

        foreach ($transacciones as $transaccion) {
            $codigo = $transaccion->accountingCode->code;
            $descripcion = $transaccion->accountingCode->description;
            $currency = $transaccion->currency;
            $amount = $transaccion->amount;

            if (!isset($movimientos[$codigo])) {
                $movimientos[$codigo] = [
                    'code' => $codigo,
                    'codigo' => $codigo, // Alias para compatibilidad
                    'description' => $descripcion,
                    'descripcion' => $descripcion, // Alias para compatibilidad
                    'VES' => 0,
                    'USD' => 0,
                    'COP' => 0,
                ];
            }

            $movimientos[$codigo][$currency] += $amount;
        }

        // Ordenar por código
        ksort($movimientos);

        \Log::info("getMovementsByType - Movement {$movementId} - Movimientos agrupados: " . count($movimientos) . " códigos");

        return array_values($movimientos);
    }

    /**
     * Aplicar filtros geográficos según el nivel del usuario
     * IMPORTANTE: Filtra por ubicación geográfica, NO por user_id
     * Esto permite que usuarios del mismo nivel vean registros de otros usuarios del mismo territorio
     */
    private function applyGeographicFilters($query, $user): void
    {
        // Roles sectoriales ven TODOS los registros del mismo sector (sin importar quién los creó)
        if ($user->hasAnyRole(['Presbítero Sectorial', 'Tesorero Sectorial', 'Contralor Sectorial']) && $user->sector_id) {
            $query->where('sector_id', $user->sector_id);
            \Log::info("Aplicando filtro sectorial: sector_id = {$user->sector_id}");
        } 
        // Supervisor distrital ve TODOS los registros del mismo distrito
        elseif ($user->hasRole('Supervisor Distrital') && $user->district_id) {
            $query->where('district_id', $user->district_id);
            \Log::info("Aplicando filtro distrital: district_id = {$user->district_id}");
        } 
        // Roles regionales ven TODOS los registros de la misma región
        elseif ($user->hasAnyRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional']) && $user->region_id) {
            $query->where('region_id', $user->region_id);
            \Log::info("Aplicando filtro regional: region_id = {$user->region_id}");
        }
        // Los usuarios nacionales ven TODOS los registros (sin filtros geográficos)
        elseif ($user->hasAnyRole(['Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional'])) {
            \Log::info("Usuario nacional: sin filtros geográficos");
        }
        // Usuarios sin rol reconocido: sin acceso
        else {
            $query->whereRaw('1 = 0');
            \Log::warning("Usuario sin rol contable reconocido: bloqueando acceso");
        }
    }

    /**
     * Calcular totales por moneda
     */
    private function calculateTotals(array $movimientos): array
    {
        $totales = ['VES' => 0, 'USD' => 0, 'COP' => 0];

        foreach ($movimientos as $movimiento) {
            $totales['VES'] += $movimiento['VES'];
            $totales['USD'] += $movimiento['USD'];
            $totales['COP'] += $movimiento['COP'];
        }

        return $totales;
    }

    /**
     * Calcular saldos (ingresos - egresos)
     */
    private function calculateBalances(array $ingresos, array $egresos): array
    {
        return [
            'VES' => $ingresos['VES'] - $egresos['VES'],
            'USD' => $ingresos['USD'] - $egresos['USD'],
            'COP' => $ingresos['COP'] - $egresos['COP'],
        ];
    }

    /**
     * Formatear período para mostrar en el PDF
     */
    private function formatPeriod(string $period, $fechaInicio, $fechaFin, ?string $monthYear = null): string
    {
        $inicio = Carbon::parse($fechaInicio)->format('d/m/Y');
        $fin = Carbon::parse($fechaFin)->format('d/m/Y');

        return match($period) {
            'mes' => 'Mes de ' . Carbon::createFromFormat('Y-m', $monthYear)->translatedFormat('F Y'),
            'ultimo_trimestre' => "Último Trimestre ({$inicio} - {$fin})",
            'ultimo_semestre' => "Último Semestre ({$inicio} - {$fin})",
            'ultimo_año' => "Último Año ({$inicio} - {$fin})",
            default => "Período ({$inicio} - {$fin})",
        };
    }

    /**
     * Obtener texto del nivel para el encabezado
     */
    private function getNivelTexto(string $nivel): string
    {
        return match($nivel) {
            'sectorial' => 'SECTOR',
            'distrital' => 'DISTRITO', 
            'regional' => 'REGIÓN',
            'nacional' => 'NACIONAL',
            default => 'NIVEL'
        };
    }

    /**
     * Obtener alcance del reporte según el nivel
     */
    private function getReportScope(string $level): string
    {
        $scopes = [
            'nacional' => 'Incluye transacciones de todos los tesoreros nacionales y es visible para roles nacionales',
            'regional' => 'Incluye transacciones de todos los tesoreros regionales de la región y es visible para superintendentes y contralores regionales',
            'distrital' => 'Incluye transacciones registradas en el distrito y visibles para supervisores distritales',
            'sectorial' => 'Incluye transacciones de todos los tesoreros sectoriales del sector y es visible para presbíteros y contralores sectoriales'
        ];

        return $scopes[$level] ?? 'Transacciones según permisos del usuario';
    }

    /**
     * Formatear nombre del usuario (primer nombre + primer apellido)
     */
    private function formatUserName(?string $name, ?string $lastname): string
    {
        // Obtener primer nombre
        $firstName = '';
        if ($name) {
            $nameParts = array_filter(explode(' ', trim($name)));
            $firstName = $nameParts[0] ?? '';
        }
        
        // Obtener primer apellido
        $firstLastname = '';
        if ($lastname) {
            $lastnameParts = array_filter(explode(' ', trim($lastname)));
            $firstLastname = $lastnameParts[0] ?? '';
        }
        
        // Combinar primer nombre + primer apellido
        if ($firstName && $firstLastname) {
            return $firstName . ' ' . $firstLastname;
        } elseif ($firstName) {
            return $firstName;
        } elseif ($firstLastname) {
            return $firstLastname;
        } else {
            return 'Usuario';
        }
    }

    /**
     * Limpiar nombre de archivo
     */
    private function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename);
    }

    /**
     * Formatear moneda para mostrar
     */
    public function formatCurrency(float $amount, string $currency): string
    {
        $symbols = [
            'VES' => 'Bs.',
            'USD' => 'US$',
            'COP' => 'COP$',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . ' ' . number_format($amount, 2, ',', '.');
    }

    /**
     * Obtener el nivel territorial del usuario basado en sus roles
     * Este método debe estar en el servicio ya que lo estamos llamando
     */
    private function getUserTerritorialLevel(): array
    {
        $u = auth()->user();
        if (!$u) return ['level' => 'unknown', 'name' => 'Sin usuario', 'id' => null, 'display' => 'Usuario desconocido'];

        if ($u->hasAnyRole(['Presbítero Sectorial', 'Tesorero Sectorial', 'Contralor Sectorial'])) {
            if ($u->sector_id) {
                $sector = DB::table('sectors')->where('id', $u->sector_id)->first();
                return [
                    'level' => 'sectorial',
                    'name' => $sector ? $sector->name : 'Sector desconocido',
                    'id' => $u->sector_id,
                    'display' => 'Sectorial - ' . ($sector ? $sector->name : 'Desconocido')
                ];
            }
        }

        if ($u->hasRole('Supervisor Distrital')) {
            if ($u->district_id) {
                $district = DB::table('districts')->where('id', $u->district_id)->first();
                return [
                    'level' => 'distrital',
                    'name' => $district ? $district->name : 'Distrito desconocido',
                    'id' => $u->district_id,
                    'display' => 'Distrital - ' . ($district ? $district->name : 'Desconocido')
                ];
            }
        }

        if ($u->hasAnyRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional'])) {
            if ($u->region_id) {
                $region = DB::table('regions')->where('id', $u->region_id)->first();
                return [
                    'level' => 'regional',
                    'name' => $region ? $region->name : 'Región desconocida',
                    'id' => $u->region_id,
                    'display' => 'Regional - ' . ($region ? $region->name : 'Desconocido')
                ];
            }
        }

        if ($u->hasAnyRole(['Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional'])) {
            return [
                'level' => 'nacional',
                'name' => 'Nacional',
                'id' => null,
                'display' => 'Nacional'
            ];
        }

        return [
            'level' => 'unknown',
            'name' => 'Sin nivel asignado',
            'id' => null,
            'display' => 'Usuario sin nivel territorial'
        ];
    }

    /**
     * Obtener nombre del sector
     */
    private function getSectorName(?int $sectorId): string
    {
        if (!$sectorId) return 'Sector sin asignar';
        
        $sector = DB::table('sectors')->where('id', $sectorId)->first();
        return $sector ? $sector->name : "Sector {$sectorId}";
    }

    /**
     * Obtener nombre del distrito
     */
    private function getDistrictName(?int $districtId): string
    {
        if (!$districtId) return 'Distrito sin asignar';
        
        $district = DB::table('districts')->where('id', $districtId)->first();
        return $district ? $district->name : "Distrito {$districtId}";
    }

    /**
     * Obtener nombre de la región
     */
    private function getRegionName(?int $regionId): string
    {
        if (!$regionId) return 'Región sin asignar';
        
        $region = DB::table('regions')->where('id', $regionId)->first();
        return $region ? $region->name : "Región {$regionId}";
    }
}