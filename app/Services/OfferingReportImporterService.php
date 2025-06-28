<?php

namespace App\Services;

use App\Models\{
    OfferingReport, OfferingItem, TreasuryAllocation,
    Pastor, Church, OfferingDistribution, Treasury,
    PastorMinistry
};
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OfferingReportImporterService
{
    private $sectorId;
    private $month;
    private $usdRate;
    private $copRate;
    private $userId;

    private $categoryMapping = [
        'diezmos' => 1,
        'el_poder_del_uno' => 2,
        'sede_nacional' => 3,
        'convencion_nacional' => 6,
        'unica_sectorial' => 7,
        'campamento_de_retiros' => 8,
        'abisop' => 9
    ];

    public function __construct()
    {
        $this->userId = auth()->id() ?? 1;
    }

    public function importFromExcel(
        string $filePath,
        int $sectorId,
        string $month,
        float $usdRate,
        float $copRate
    ): array {
        $this->sectorId = $sectorId;
        $this->month = $month;
        $this->usdRate = $usdRate;
        $this->copRate = $copRate;

        DB::beginTransaction();

        try {
            $data = $this->readExcelFile($filePath);
            $results = $this->processRows($data);
            DB::commit();

            return [
                'success' => true,
                'imported_count' => $results['imported'],
                'skipped_count' => $results['skipped'],
                'errors' => $results['errors'],
                'log' => $results['log'],
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }
    }

    private function readExcelFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];

        $row = 2;
        while (true) {
            $churchName = trim($worksheet->getCell("B{$row}")->getCalculatedValue() ?? '');
            $pastorName = trim($worksheet->getCell("C{$row}")->getCalculatedValue() ?? '');

            if (empty($churchName) && empty($pastorName)) {
                break;
            }

            if (!empty($churchName) || !empty($pastorName)) {
                $data[] = [
                    'numero' => $worksheet->getCell("A{$row}")->getCalculatedValue(),
                    'iglesia' => $churchName,
                    'pastor' => $pastorName,
                    'diezmos' => $this->parseAmount($worksheet->getCell("D{$row}")->getCalculatedValue()),
                    'el_poder_del_uno' => $this->parseAmount($worksheet->getCell("E{$row}")->getCalculatedValue()),
                    'sede_nacional' => $this->parseAmount($worksheet->getCell("F{$row}")->getCalculatedValue()),
                    'convencion_nacional' => $this->parseAmount($worksheet->getCell("G{$row}")->getCalculatedValue()),
                    'unica_sectorial' => $this->parseAmount($worksheet->getCell("H{$row}")->getCalculatedValue()),
                    'campamento_de_retiros' => $this->parseAmount($worksheet->getCell("I{$row}")->getCalculatedValue()),
                    'abisop' => $this->parseAmount($worksheet->getCell("J{$row}")->getCalculatedValue()),
                    'total' => $this->parseAmount($worksheet->getCell("K{$row}")->getCalculatedValue()),
                ];
            }

            $row++;
            if ($row > 1000) break;
        }

        return $data;
    }

    private function processRows(array $data): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $log = [];

        foreach ($data as $index => $rowData) {
            try {
                $result = $this->processRow($rowData, $index + 1);

                if ($result['success']) {
                    $imported++;
                    $log[] = "✅ Fila " . ($index + 1) . ": " . $result['message'];
                } else {
                    $skipped++;
                    $log[] = "⚠️ Fila " . ($index + 1) . ": " . $result['message'];
                }
            } catch (\Exception $e) {
                $errors[] = "❌ Fila " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return compact('imported', 'skipped', 'errors', 'log');
    }

    private function processRow(array $rowData, int $rowNumber): array
    {
        $pastor = $this->findPastor($rowData['pastor']);
        if (!$pastor) {
            return [
                'success' => false,
                'message' => "Pastor '{$rowData['pastor']}' no encontrado",
            ];
        }

        $church = $this->findChurch($rowData['iglesia'], $pastor->id);

        $existingReport = OfferingReport::where('pastor_id', $pastor->id)
            ->where('month', $this->month)
            ->where('sector_id', $this->sectorId)
            ->first();

        if ($existingReport) {
            return [
                'success' => false,
                'message' => "Reporte ya existe para pastor {$pastor->name} en {$this->month}",
            ];
        }

        $totalCalculated = $this->calculateTotal($rowData);

        return DB::transaction(function () use ($pastor, $church, $rowData, $totalCalculated) {
            $offeringReport = OfferingReport::create([
                'pastor_id' => $pastor->id,
                'church_id' => $church?->id,
                'month' => $this->month,
                'region_id' => $pastor->region_id,
                'district_id' => $pastor->district_id,
                'sector_id' => $this->sectorId,
                'user_id' => $this->userId,
                'usd_rate' => $this->usdRate,
                'cop_rate' => $this->copRate,
                'total_bs' => $totalCalculated,
                'grand_total_bs' => $totalCalculated,
                'status' => 'aprobado',
            ]);

            $this->createOfferingItems($offeringReport->id, $rowData);
            $this->createTreasuryAllocationsFixed($offeringReport->id, $rowData);

            return [
                'success' => true,
                'message' => "Importado: {$pastor->name}" . ($church ? " - {$church->name}" : " (SIN IGLESIA)"),
            ];
        });
    }

    /**
     * 🔧 MÉTODO MEJORADO: Búsqueda inteligente de pastores
     * Maneja nombres compuestos y evita confusiones entre pastores similares
     */
    private function findPastor(string $pastorName): ?Pastor
    {
        if (empty($pastorName)) {
            return null;
        }

        // Limpiar y dividir el nombre completo
        $nameParts = array_filter(explode(' ', trim(strtoupper($pastorName))));
        
        if (count($nameParts) < 2) {
            return null;
        }

        // Estrategia 1: BÚSQUEDA EXACTA COMPLETA
        $exactMatch = $this->findPastorByExactMatch($nameParts);
        if ($exactMatch) {
            $this->logPastorSearch($pastorName, $exactMatch, 'exact_match');
            return $exactMatch;
        }

        // Estrategia 2: BÚSQUEDA POR COMBINACIONES DE NOMBRES
        $combinationMatch = $this->findPastorByCombinations($nameParts);
        if ($combinationMatch) {
            $this->logPastorSearch($pastorName, $combinationMatch, 'combination_match');
            return $combinationMatch;
        }

        // Estrategia 3: BÚSQUEDA FUZZY (coincidencia parcial)
        $fuzzyMatch = $this->findPastorByFuzzyMatch($nameParts);
        if ($fuzzyMatch) {
            $this->logPastorSearch($pastorName, $fuzzyMatch, 'fuzzy_match');
            return $fuzzyMatch;
        }

        $this->logPastorSearch($pastorName, null, 'not_found');
        return null;
    }

    /**
     * Estrategia 1: Búsqueda exacta completa
     * Ejemplo: "GILBERT JOSE MARQUEZ" busca exactamente ese nombre
     */
    private function findPastorByExactMatch(array $nameParts): ?Pastor
    {
        // Dividir en nombres y apellidos (últimas 1-2 palabras son apellidos)
        $totalParts = count($nameParts);
        
        // Probar diferentes divisiones nombre/apellido
        for ($apellidoCount = 1; $apellidoCount <= 2 && $apellidoCount < $totalParts; $apellidoCount++) {
            $nombres = implode(' ', array_slice($nameParts, 0, $totalParts - $apellidoCount));
            $apellidos = implode(' ', array_slice($nameParts, $totalParts - $apellidoCount));

            $pastor = Pastor::whereRaw('UPPER(TRIM(name)) = ?', [$nombres])
                        ->whereRaw('UPPER(TRIM(lastname)) = ?', [$apellidos])
                        ->where('sector_id', $this->sectorId)
                        ->first();

            if ($pastor) {
                return $pastor;
            }
        }

        return null;
    }

    /**
     * Estrategia 2: Búsqueda por combinaciones inteligentes
     * Maneja casos como "GILBERT JOSE" vs "GILBERT ISRAEL"
     */
    private function findPastorByCombinations(array $nameParts): ?Pastor
    {
        $totalParts = count($nameParts);
        $candidates = collect();

        // Diferentes combinaciones de nombres
        $combinations = [
            // Primer nombre + último apellido
            ['nombres' => [$nameParts[0]], 'apellidos' => [$nameParts[$totalParts - 1]]],
            
            // Primeros 2 nombres + último apellido (si hay suficientes partes)
            $totalParts >= 3 ? [
                'nombres' => [$nameParts[0], $nameParts[1]], 
                'apellidos' => [$nameParts[$totalParts - 1]]
            ] : null,
            
            // Primer nombre + últimos 2 apellidos (si hay suficientes partes)
            $totalParts >= 3 ? [
                'nombres' => [$nameParts[0]], 
                'apellidos' => [$nameParts[$totalParts - 2], $nameParts[$totalParts - 1]]
            ] : null,
        ];

        foreach (array_filter($combinations) as $combo) {
            $nombrePattern = implode(' ', $combo['nombres']);
            $apellidoPattern = implode(' ', $combo['apellidos']);

            $pastors = Pastor::where('sector_id', $this->sectorId)
                            ->whereRaw('UPPER(name) LIKE ?', ["%{$nombrePattern}%"])
                            ->whereRaw('UPPER(lastname) LIKE ?', ["%{$apellidoPattern}%"])
                            ->get();

            foreach ($pastors as $pastor) {
                $score = $this->calculateNameSimilarity($nameParts, $pastor);
                $candidates->push(['pastor' => $pastor, 'score' => $score]);
            }
        }

        // Ordenar por mayor similitud y evitar duplicados
        $bestMatch = $candidates->unique(function($item) {
            return $item['pastor']->id;
        })->sortByDesc('score')->first();
        
        // Solo devolver si la similitud es alta (> 70%)
        return ($bestMatch && $bestMatch['score'] > 0.7) ? $bestMatch['pastor'] : null;
    }

    /**
     * Estrategia 3: Búsqueda fuzzy para casos complicados
     */
    private function findPastorByFuzzyMatch(array $nameParts): ?Pastor
    {
        $firstName = $nameParts[0];
        $lastName = $nameParts[count($nameParts) - 1];

        // Búsqueda más flexible
        $pastors = Pastor::where('sector_id', $this->sectorId)
                        ->whereRaw('UPPER(name) LIKE ?', ["%{$firstName}%"])
                        ->whereRaw('UPPER(lastname) LIKE ?', ["%{$lastName}%"])
                        ->get();

        if ($pastors->count() == 1) {
            return $pastors->first();
        }

        if ($pastors->count() == 0) {
            return null;
        }

        // Si hay múltiples candidatos, calcular similitud
        $candidates = collect();
        foreach ($pastors as $pastor) {
            $score = $this->calculateNameSimilarity($nameParts, $pastor);
            $candidates->push(['pastor' => $pastor, 'score' => $score]);
        }

        $bestMatch = $candidates->sortByDesc('score')->first();
        
        // Solo devolver si la similitud es razonable (> 60%)
        return ($bestMatch && $bestMatch['score'] > 0.6) ? $bestMatch['pastor'] : null;
    }

    /**
     * Calcula la similitud entre el nombre del Excel y el nombre del pastor en BD
     */
    private function calculateNameSimilarity(array $excelNameParts, Pastor $pastor): float
    {
        $excelFullName = implode(' ', $excelNameParts);
        $pastorFullName = strtoupper(trim($pastor->name . ' ' . $pastor->lastname));

        // Usar similar_text para calcular similitud
        similar_text($excelFullName, $pastorFullName, $percentage);
        
        // Bonus por coincidencia exacta de primer nombre y apellido
        $firstNameMatch = str_contains(strtoupper($pastor->name), $excelNameParts[0]) ? 0.2 : 0;
        $lastNameMatch = str_contains(strtoupper($pastor->lastname), $excelNameParts[count($excelNameParts) - 1]) ? 0.2 : 0;

        return ($percentage / 100) + $firstNameMatch + $lastNameMatch;
    }

    /**
     * 🔧 Log detallado para debugging
     */
    private function logPastorSearch(string $searchName, ?Pastor $foundPastor, string $strategy): void
    {
        if ($foundPastor) {
            \Log::info("Pastor encontrado", [
                'excel_name' => $searchName,
                'found_pastor' => $foundPastor->name . ' ' . $foundPastor->lastname,
                'pastor_id' => $foundPastor->id,
                'strategy' => $strategy,
                'sector_id' => $this->sectorId
            ]);
        } else {
            \Log::warning("Pastor NO encontrado", [
                'excel_name' => $searchName,
                'sector_id' => $this->sectorId,
                'available_pastors' => Pastor::where('sector_id', $this->sectorId)
                                            ->pluck('name', 'lastname')
                                            ->map(fn($name, $lastname) => "$name $lastname")
                                            ->values()
                                            ->take(5)
                                            ->toArray()
            ]);
        }
    }

    private function findChurch(string $churchName, int $pastorId): ?Church
    {
        if (empty($churchName)) return null;

        $ministry = PastorMinistry::where('pastor_id', $pastorId)
            ->where('active', 1)
            ->with('church')
            ->first();

        if ($ministry && $ministry->church) {
            return $ministry->church;
        }

        return Church::whereRaw('UPPER(name) LIKE ?', ['%' . strtoupper(trim($churchName)) . '%'])
            ->where('sector_id', $this->sectorId)
            ->first();
    }

    private function createOfferingItems(int $reportId, array $rowData): void
    {
        foreach ($this->categoryMapping as $field => $categoryId) {
            $amount = $rowData[$field] ?? 0;
            if ($amount > 0) {
                OfferingItem::create([
                    'offering_report_id' => $reportId,
                    'offering_category_id' => $categoryId,
                    'subtotal_bs' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function createTreasuryAllocationsFixed(int $reportId, array $rowData): void
    {
        // ✅ Prevención contra duplicación
        $existingAllocations = TreasuryAllocation::where('offering_report_id', $reportId)->exists();
        if ($existingAllocations) {
            \Log::warning("⛔️ Deducciones ya existen para el reporte {$reportId}, se omite creación.");
            return;
        }

        $distributions = OfferingDistribution::with('targetTreasury')->get()->groupBy('offering_category_id');
        $allocationsToInsert = [];

        foreach ($this->categoryMapping as $field => $categoryId) {
            $baseAmount = $rowData[$field] ?? 0;

            if ($baseAmount > 0 && isset($distributions[$categoryId])) {
                foreach ($distributions[$categoryId] as $distribution) {
                    $allocationAmount = $baseAmount * ($distribution->percentage / 100);
                    $allocationsToInsert[] = [
                        'offering_report_id' => $reportId,
                        'treasury_id' => $distribution->target_treasury_id,
                        'offering_category_id' => $categoryId,
                        'amount' => $allocationAmount,
                        'percentage' => $distribution->percentage,
                        'month' => $this->month,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (!empty($allocationsToInsert)) {
            \Log::info("Inserting " . count($allocationsToInsert) . " allocations for report {$reportId}");
            $chunks = array_chunk($allocationsToInsert, 100);
            foreach ($chunks as $chunk) {
                DB::table('treasury_allocations')->insert($chunk);
            }
        }
    }

    private function parseAmount($value): float
    {
        if (empty($value)) return 0;
        $cleaned = str_replace(['.', ','], ['', '.'], $value);
        return (float) $cleaned;
    }

    private function calculateTotal(array $rowData): float
    {
        return array_reduce(array_keys($this->categoryMapping), fn($carry, $key) => $carry + ($rowData[$key] ?? 0), 0);
    }
}