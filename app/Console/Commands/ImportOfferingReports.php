<?php

namespace App\Console\Commands;

use App\Services\OfferingReportImporterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ImportOfferingReports extends Command
{
    protected $signature = 'offering-reports:import 
                            {file : Ruta del archivo Excel}
                            {--sector= : ID del sector}
                            {--month= : Mes (formato YYYY-MM)}
                            {--usd-rate= : Tasa USD}
                            {--cop-rate= : Tasa COP}
                            {--dry-run : Simular sin guardar}';

    protected $description = 'Importar reportes de ofrendas desde archivo Excel';

    public function handle()
    {
        $filePath = $this->argument('file');
        $sectorId = $this->option('sector');
        $month = $this->option('month');
        $usdRate = $this->option('usd-rate');
        $copRate = $this->option('cop-rate');
        $dryRun = $this->option('dry-run');

        // Validaciones
        if (!file_exists($filePath)) {
            $this->error("❌ Archivo no encontrado: {$filePath}");
            return 1;
        }

        if (!$sectorId || !$month || !$usdRate || !$copRate) {
            $this->error("❌ Faltan parámetros requeridos: --sector, --month, --usd-rate, --cop-rate");
            return 1;
        }

        // Autenticar como admin
        $this->authenticateAsAdmin();

        $this->info("🔄 Iniciando importación de reportes");
        $this->info("📁 Archivo: {$filePath}");
        $this->info("🏢 Sector: {$sectorId}");
        $this->info("📅 Mes: {$month}");
        $this->info("💵 Tasa USD: {$usdRate}");
        $this->info("🪙 Tasa COP: {$copRate}");
        
        if ($dryRun) {
            $this->warn("🧪 MODO DRY-RUN - No se guardarán cambios");
        }

        $this->newLine();

        try {
            // Primero hacer una lectura de prueba
            $this->info("📖 Leyendo archivo Excel...");
            
            $importer = new OfferingReportImporterService();
            
            if ($dryRun) {
                // En modo dry-run, solo leer y mostrar estructura
                $this->previewFile($filePath);
                return 0;
            }

            // Importación real
            $result = $importer->importFromExcel(
                $filePath,
                (int) $sectorId,
                $month,
                (float) $usdRate,
                (float) $copRate
            );

            if ($result['success']) {
                $this->info("✅ Importación completada exitosamente");
                
                $this->table(
                    ['Métrica', 'Valor'],
                    [
                        ['Reportes importados', $result['imported_count']],
                        ['Reportes omitidos', $result['skipped_count']],
                        ['Errores', count($result['errors'])],
                    ]
                );

                if ($this->option('verbose')) {
                    $this->info("\n📋 Detalle de importación:");
                    foreach ($result['log'] as $logEntry) {
                        $this->line($logEntry);
                    }
                }

                if (!empty($result['errors'])) {
                    $this->warn("\n⚠️ Errores encontrados:");
                    foreach ($result['errors'] as $error) {
                        $this->line($error);
                    }
                }

            } else {
                $this->error("❌ Error en la importación: " . $result['error']);
                if (isset($result['file']) && isset($result['line'])) {
                    $this->line("📍 Archivo: {$result['file']}:{$result['line']}");
                }
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("💥 Error inesperado: " . $e->getMessage());
            $this->line("📍 {$e->getFile()}:{$e->getLine()}");
            return 1;
        } finally {
            Auth::logout();
        }

        return 0;
    }

    private function authenticateAsAdmin(): void
    {
        $adminUser = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Administrador', 'Obispo Presidente', 'Tesorero Nacional']);
        })->first();

        if ($adminUser) {
            Auth::login($adminUser);
            $this->info("🔐 Autenticado como: {$adminUser->name}");
        }
    }

    private function previewFile(string $filePath): void
    {
        $this->info("🔍 PREVIEW DEL ARCHIVO (modo dry-run)");
        $this->line("─────────────────────────────────────");

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Mostrar headers
            $this->info("📋 Headers detectados:");
            for ($col = 'A'; $col <= 'K'; $col++) {
                $header = $worksheet->getCell("{$col}1")->getCalculatedValue();
                $this->line("  {$col}: {$header}");
            }

            $this->newLine();

            // Mostrar primeras 5 filas de datos
            $this->info("📊 Primeras 5 filas de datos:");
            for ($row = 2; $row <= 6; $row++) {
                $iglesia = $worksheet->getCell("B{$row}")->getCalculatedValue();
                $pastor = $worksheet->getCell("C{$row}")->getCalculatedValue();
                $diezmos = $worksheet->getCell("D{$row}")->getCalculatedValue();
                $total = $worksheet->getCell("K{$row}")->getCalculatedValue();

                if (!empty($iglesia) || !empty($pastor)) {
                    $this->line("  Fila {$row}: {$iglesia} | {$pastor} | Diezmos: {$diezmos} | Total: {$total}");
                }
            }

            $this->newLine();
            $this->info("✅ Archivo leído correctamente. Ejecute sin --dry-run para importar.");

        } catch (\Exception $e) {
            $this->error("❌ Error leyendo archivo: " . $e->getMessage());
        }
    }
}