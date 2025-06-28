<?php

namespace App\Filament\Resources\AccountingSummaryResource\Pages;

use App\Filament\Resources\AccountingSummaryResource;
use App\Services\AccountingPDFService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class ListAccountingSummaries extends ListRecords
{
    protected static string $resource = AccountingSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Action::make('generate_pdf')
                ->label('PDF Resumen Contable')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Select::make('period')
                        ->label('Período')
                        ->options([
                            'mes' => 'Mes específico',
                            'ultimo_trimestre' => 'Último Trimestre',
                            'ultimo_semestre' => 'Último Semestre',
                            'ultimo_año' => 'Último Año',
                        ])
                        ->required()
                        ->native(false)
                        ->default('mes')
                        ->live(),
                    
                    Select::make('month_year')
                        ->label('Seleccionar Mes')
                        ->options(function () {
                            $pdfService = app(AccountingPDFService::class);
                            return $pdfService->getMonthOptions();
                        })
                        ->required()
                        ->native(false)
                        ->default(Carbon::now()->subMonth()->format('Y-m'))
                        ->visible(fn (callable $get) => $get('period') === 'mes'),
                    
                    Select::make('accounting_id')
                        ->label('Contabilidad')
                        ->options(function () {
                            $pdfService = app(AccountingPDFService::class);
                            return $pdfService->getAvailableAccountings(auth()->id());
                        })
                        ->required()
                        ->native(false)
                        ->default(function () {
                            $pdfService = app(AccountingPDFService::class);
                            return $pdfService->getDefaultAccounting(auth()->id());
                        })
                        ->disabled()
                        ->dehydrated(),
                ])
                ->action(function (array $data) {
                    try {
                        \Log::info('PDF Action - Data recibida: ' . json_encode($data));
                        \Log::info('PDF Action - Usuario: ' . auth()->user()->name);
                        
                        // Validar que el período haya concluido
                        $validacion = self::validarPeriodoConcluido($data['period']);
                        if (!$validacion['valido']) {
                            Notification::make()
                                ->title('Período no disponible')
                                ->body($validacion['mensaje'])
                                ->warning()
                                ->send();
                            return null;
                        }
                        
                        $pdfService = app(AccountingPDFService::class);
                        
                        // Validar datos requeridos
                        if ($data['period'] === 'mes' && empty($data['month_year'])) {
                            throw new \Exception('Debe seleccionar un mes específico');
                        }
                        
                        if (empty($data['accounting_id'])) {
                            throw new \Exception('No se pudo determinar la contabilidad');
                        }
                        
                        \Log::info('PDF Action - Generando PDF...');
                        
                        // Generar y descargar PDF
                        return $pdfService->generatePDF($data, auth()->id());
                        
                    } catch (\Exception $e) {
                        \Log::error('PDF Action - Error: ' . $e->getMessage());
                        \Log::error('PDF Action - Stack trace: ' . $e->getTraceAsString());
                        
                        Notification::make()
                            ->title('Error al generar PDF')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        
                        return null;
                    }
                })
                ->modalWidth('md')
                ->modalSubmitActionLabel('Generar PDF')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }

    /**
     * Validar que el período solicitado haya concluido
     */
    private static function validarPeriodoConcluido(string $periodo): array
    {
        $ahora = Carbon::now();
        
        return match($periodo) {
            'mes' => [
                'valido' => true,
                'mensaje' => '' // Los meses específicos siempre son válidos (el usuario elige)
            ],
            
            'ultimo_trimestre' => [
                'valido' => $ahora->day >= 5, // Dar 5 días de margen después del fin del mes
                'mensaje' => $ahora->day < 5 
                    ? 'El último trimestre aún no ha concluido completamente. Por favor, espere hasta después del día 5 del mes para generar este reporte.'
                    : ''
            ],
            
            'ultimo_semestre' => [
                'valido' => $ahora->day >= 10, // Dar 10 días de margen
                'mensaje' => $ahora->day < 10
                    ? 'El último semestre aún no ha concluido completamente. Por favor, espere hasta después del día 10 del mes para generar este reporte.'
                    : ''
            ],
            
            'ultimo_año' => [
                'valido' => $ahora->day >= 15, // Dar 15 días de margen
                'mensaje' => $ahora->day < 15
                    ? 'El último año aún no ha concluido completamente. Por favor, espere hasta después del día 15 del mes para generar este reporte.'
                    : ''
            ],
            
            default => [
                'valido' => true,
                'mensaje' => ''
            ]
        };
    }
}