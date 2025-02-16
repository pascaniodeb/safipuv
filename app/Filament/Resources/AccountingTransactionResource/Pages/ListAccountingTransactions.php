<?php

namespace App\Filament\Resources\AccountingTransactionResource\Pages;

use App\Filament\Resources\AccountingTransactionResource;
use App\Models\AccountingTransaction;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAccountingTransactions extends ListRecords
{
    protected static string $resource = AccountingTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Registro'),

            Action::make('Cerrar Mes')
                ->label('Cerrar Mes')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Cierre de Mes')
                ->modalSubheading('¿Está seguro de que desea cerrar el mes actual? Esta acción no se puede deshacer.')
                ->modalButton('Sí, Cerrar Mes')
                ->color('danger')
                ->icon('heroicon-o-calendar')
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