<?php

namespace App\Filament\Resources\AccountingTransactionResource\Pages;

use App\Filament\Resources\AccountingTransactionResource;
use App\Models\AccountingTransaction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\CreateAction;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAccountingTransactions extends ListRecords
{
    protected static string $resource = AccountingTransactionResource::class;

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
                ->requiresConfirmation()
                ->modalHeading('Confirmar Cierre de Mes')
                ->modalSubheading('¿Está seguro de que desea cerrar el mes actual? Esta acción no se puede deshacer.')
                ->modalButton('Sí, Cerrar Mes')
                ->color('danger')
                ->icon('heroicon-o-calendar')
                ->visible(fn () => Auth::user()->hasAnyRole($allowedRoles)) // ✅ Filtra según los roles permitidos
                ->action(function () {
                    $month = now()->subMonth()->format('Y-m'); // Cierra el mes anterior
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
        ];
    }
}