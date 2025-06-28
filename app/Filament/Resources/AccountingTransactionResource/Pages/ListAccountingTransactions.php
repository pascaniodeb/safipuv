<?php

namespace App\Filament\Resources\AccountingTransactionResource\Pages;

use App\Filament\Resources\AccountingTransactionResource;
use App\Models\AccountingTransaction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Support\Facades\DB;
use Filament\Actions;
use App\Models\AccountingSummary;
use App\Traits\HasAccountingAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListAccountingTransactions extends ListRecords
{
    use HasAccountingAccess;
    
    protected static string $resource = AccountingTransactionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\AccountingTransactionResource\Widgets\AccountingTransactionStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        // 🔹 Definir los roles permitidos ANTES de usarlos en las acciones
        $allowedRoles = ['Tesorero Sectorial', 'Supervisor Distrital', 'Tesorero Regional', 'Tesorero Nacional'];

        return [
            Actions\CreateAction::make()
                ->label('Nuevo Registro')
                ->visible(fn () => Auth::user()->hasAnyRole($allowedRoles)), // ✅ Filtra según los roles permitidos

            Action::make('Cerrar Mes')
                ->label('Cerrar Mes')
                ->icon('heroicon-o-calendar')
                ->color('danger')
                ->visible(fn () => Auth::user()->hasAnyRole($allowedRoles))
                ->form([
                    Forms\Components\DatePicker::make('mes')
                        ->label('Mes a Cerrar')
                        ->native(false)
                        ->displayFormat('m/Y') // Lo que ve el usuario
                        ->format('Y-m')        // Lo que se recibe como $data['mes']
                        ->maxDate(now())
                        ->required(),
                ])
                ->modalHeading('Seleccionar el Mes a Cerrar')
                ->modalSubheading('¿Está seguro de que desea cerrar el mes seleccionado? Esta acción no se puede deshacer.')
                ->modalButton('Sí, Cerrar Mes')
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $month = $data['mes']; // formato: '2025-01'
                    $accountingId = auth()->user()->treasury->accounting->id ?? null;
            
                    if (!$accountingId) {
                        Notification::make()
                            ->title('Error')
                            ->body('No se pudo determinar la contabilidad asociada.')
                            ->danger()
                            ->send();
                        return;
                    }
            
                    try {
                        // 🔁 Creamos un rango de fechas para ese mes
                        $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                        $end   = $start->copy()->endOfMonth();
            
                        // Validamos si ya existe un saldo inicial para ese mes
                        $saldosIniciales = DB::table('accounting_transactions')
                            ->where('accounting_id', $accountingId)
                            ->whereBetween('transaction_date', [$start, $end])
                            ->whereIn('currency', ['VES', 'USD', 'COP'])
                            ->where('description', "Saldo inicial de {$month}")
                            ->pluck('currency')
                            ->toArray();

                        if (count($saldosIniciales) === 3) {
                            throw new \Exception("Ya se cerró el mes {$month} con las 3 monedas (VES, USD, COP).");
                        }
            
                        // Aquí debes llamar tu método real para cerrar el mes,
                        // pasando $start->format('Y-m-d') si necesitas la fecha exacta
                        $message = AccountingTransaction::closeMonth($month, $accountingId);
            
                        Notification::make()
                            ->title('Cierre de Mes Exitoso')
                            ->body($message)
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error en el Cierre de Mes')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

                Actions\Action::make('Generar Resumen del Mes')
                ->label('Generar Libro Mayor')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Generar Resumen Contable')
                ->modalSubheading('¿Está seguro de que desea generar el resumen contable para el mes seleccionado?')
                ->modalButton('Sí, generar')
                ->visible(fn () => Auth::user()->hasAnyRole($allowedRoles))
                ->form([
                    Forms\Components\DatePicker::make('month')
                        ->label('Mes a Generar')
                        ->format('Y-m')           // formato guardado: '2025-04'
                        ->displayFormat('m/Y')    // formato visual: '04/2025'
                        ->native(false)
                        ->required()
                        ->maxDate(now()),
                ])
                ->action(function (array $data) {
                    try {
                        $month = $data['month'];
            
                        $accounting = $this->getUserAccounting();
            
                        if (! $accounting) {
                            throw new \Exception('No se encontró contabilidad asociada al usuario.');
                        }
            
                        $accountingId = $accounting->id;
            
                        if (! \App\Models\AccountingTransaction::isMonthClosed($month, $accountingId)) {
                            throw new \Exception("No se puede generar el resumen porque el mes {$month} aún no ha sido cerrado.");
                        }
            
                        // ✅ Generar resumen mensual (sin tasas)
                        $message = \App\Models\AccountingSummary::generateForMonth(
                            $month,
                            $accountingId
                        );
            
                        Notification::make()
                            ->title('Resumen generado')
                            ->body($message)
                            ->success()
                            ->send();
            
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al generar resumen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })            
                
            
            
        ];
    }
}