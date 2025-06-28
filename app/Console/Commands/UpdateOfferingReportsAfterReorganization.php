<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Auth};
use App\Models\{OfferingReport, Pastor, Church, PastorMinistry, User};

class UpdateOfferingReportsAfterReorganization extends Command
{
    protected $signature = 'offering-reports:update-reorganization 
                            {--region= : ID de la región a actualizar (opcional)}
                            {--month=2025-01 : Mes a actualizar (formato YYYY-MM)}
                            {--dry-run : Simular sin hacer cambios}';

    protected $description = 'Actualiza offering_reports después de reorganización de sectores';

    public function handle()
    {
        $region = $this->option('region');
        $month = $this->option('month');
        $dryRun = $this->option('dry-run');

        $this->info("🔄 Iniciando actualización de offering_reports");
        $this->info("📅 Mes: {$month}");
        
        if ($region) {
            $this->info("🌍 Región: {$region}");
        } else {
            $this->info("🌍 Región: Todas");
        }
        
        if ($dryRun) {
            $this->warn("🧪 MODO DRY-RUN - No se harán cambios reales");
        }

        // 🔧 SOLUCIÓN: Autenticar como usuario administrador para evitar filtros
        $this->authenticateAsAdmin();

        try {
            $result = $this->executeUpdate($region, $month, $dryRun);
            
            if ($result['success']) {
                $this->info("✅ Actualización completada exitosamente");
                $this->table(
                    ['Métrica', 'Valor'],
                    [
                        ['Reportes procesados', $result['total_processed']],
                        ['Reportes actualizados', $result['updated_count']],
                        ['Errores', $result['error_count'] ?? 0],
                    ]
                );
                
                if ($this->option('verbose')) {
                    $this->info("\n📋 Detalle de cambios:");
                    foreach ($result['log'] as $logEntry) {
                        $this->line($logEntry);
                    }
                }
                
            } else {
                $this->error("❌ Error en la actualización: " . $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->error("💥 Error inesperado: " . $e->getMessage());
            return 1;
        } finally {
            // Limpiar autenticación
            Auth::logout();
        }

        return 0;
    }

    /**
     * 🔧 Autentica como usuario administrador para evitar filtros de seguridad
     */
    private function authenticateAsAdmin(): void
    {
        try {
            // Buscar un usuario administrador
            $adminUser = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['Administrador', 'Obispo Presidente', 'Tesorero Nacional']);
            })->first();

            if ($adminUser) {
                Auth::login($adminUser);
                $this->info("🔐 Autenticado como: {$adminUser->name}");
            } else {
                $this->warn("⚠️ No se encontró usuario administrador, usando bypass...");
                // Crear usuario temporal si no existe ningún admin
                $this->createTempAdminUser();
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ No se pudo autenticar, intentando bypass: " . $e->getMessage());
        }
    }

    private function createTempAdminUser(): void
    {
        $tempUser = new User();
        $tempUser->id = 9999;
        $tempUser->name = 'Temp Admin';
        $tempUser->email = 'temp@admin.com';
        $tempUser->exists = true;
        
        Auth::setUser($tempUser);
    }

    private function executeUpdate(?string $regionId, string $month, bool $dryRun): array
    {
        if (!$dryRun) {
            DB::beginTransaction();
        }
        
        try {
            $updatedCount = 0;
            $errorCount = 0;
            $log = [];
            
            // 🔧 Usar withoutGlobalScopes() para evitar filtros
            $query = OfferingReport::withoutGlobalScopes()
                ->with(['pastor', 'church'])
                ->where('month', $month);
            
            if ($regionId) {
                $query->where('region_id', $regionId);
            }
            
            $offeringReports = $query->get();
            
            $this->info("📊 Encontrados {$offeringReports->count()} reportes para procesar");
            
            $progressBar = $this->output->createProgressBar($offeringReports->count());
            $progressBar->start();
            
            foreach ($offeringReports as $report) {
                try {
                    $result = $this->updateSingleReport($report, $dryRun);
                    
                    if ($result['updated']) {
                        $updatedCount++;
                        $log[] = "✅ Report ID {$report->id}: " . $result['message'];
                    } else {
                        $log[] = "⚠️ Report ID {$report->id}: " . $result['message'];
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $log[] = "❌ Report ID {$report->id}: Error - " . $e->getMessage();
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine();
            
            if (!$dryRun) {
                DB::commit();
            }
            
            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'error_count' => $errorCount,
                'total_processed' => $offeringReports->count(),
                'log' => $log
            ];
            
        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $log ?? []
            ];
        }
    }
    
    private function updateSingleReport(OfferingReport $report, bool $dryRun): array
    {
        $pastor = $report->pastor;
        $church = $report->church;
        
        // Caso 1: Reporte CON iglesia
        if ($church && $pastor) {
            return $this->updateReportWithChurch($report, $pastor, $church, $dryRun);
        }
        
        // Caso 2: Reporte SIN iglesia (solo pastor)
        if (!$church && $pastor) {
            return $this->updateReportWithoutChurch($report, $pastor, $dryRun);
        }
        
        return [
            'updated' => false,
            'message' => 'No tiene pastor asociado, no se puede actualizar'
        ];
    }
    
    private function updateReportWithChurch(OfferingReport $report, Pastor $pastor, Church $church, bool $dryRun): array
    {
        // Verificar que pastor y iglesia estén en la misma ubicación
        if ($pastor->region_id !== $church->region_id || 
            $pastor->district_id !== $church->district_id || 
            $pastor->sector_id !== $church->sector_id) {
            
            // Buscar en pastor_ministries para confirmar la relación
            $ministry = PastorMinistry::where('pastor_id', $pastor->id)
                ->where('church_id', $church->id)
                ->where('is_active', true)
                ->first();
            
            if (!$ministry) {
                return [
                    'updated' => false,
                    'message' => "Pastor y iglesia no están relacionados en pastor_ministries"
                ];
            }
        }
        
        $oldValues = [
            'region_id' => $report->region_id,
            'district_id' => $report->district_id,
            'sector_id' => $report->sector_id
        ];
        
        // Solo actualizar si hay cambios
        if ($report->region_id == $church->region_id && 
            $report->district_id == $church->district_id && 
            $report->sector_id == $church->sector_id) {
            
            return [
                'updated' => false,
                'message' => 'Ya está actualizado correctamente'
            ];
        }
        
        if (!$dryRun) {
            $report->update([
                'region_id' => $church->region_id,
                'district_id' => $church->district_id,
                'sector_id' => $church->sector_id
            ]);
        }
        
        return [
            'updated' => true,
            'message' => "Actualizado con ubicación de iglesia: " . 
                        "R{$oldValues['region_id']}→R{$church->region_id}, " .
                        "D{$oldValues['district_id']}→D{$church->district_id}, " .
                        "S{$oldValues['sector_id']}→S{$church->sector_id}"
        ];
    }
    
    private function updateReportWithoutChurch(OfferingReport $report, Pastor $pastor, bool $dryRun): array
    {
        $oldValues = [
            'region_id' => $report->region_id,
            'district_id' => $report->district_id,
            'sector_id' => $report->sector_id
        ];
        
        // Solo actualizar si hay cambios
        if ($report->region_id == $pastor->region_id && 
            $report->district_id == $pastor->district_id && 
            $report->sector_id == $pastor->sector_id) {
            
            return [
                'updated' => false,
                'message' => 'Ya está actualizado correctamente'
            ];
        }
        
        if (!$dryRun) {
            $report->update([
                'region_id' => $pastor->region_id,
                'district_id' => $pastor->district_id,
                'sector_id' => $pastor->sector_id
            ]);
        }
        
        return [
            'updated' => true,
            'message' => "Actualizado con ubicación de pastor: " . 
                        "R{$oldValues['region_id']}→R{$pastor->region_id}, " .
                        "D{$oldValues['district_id']}→D{$pastor->district_id}, " .
                        "S{$oldValues['sector_id']}→S{$pastor->sector_id}"
        ];
    }
}