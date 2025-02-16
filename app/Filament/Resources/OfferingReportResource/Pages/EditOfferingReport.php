<?php

namespace App\Filament\Resources\OfferingReportResource\Pages;

use App\Filament\Resources\OfferingReportResource;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfferingReport extends EditRecord
{
    protected static string $resource = OfferingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->disabled(fn() => !Auth::user()->hasRole('Tesorero Sectorial')), // ✅ Solo el Tesorero Sectorial puede eliminar
        ];
    }


    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Editar Reporte'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Reporte')
            ->body('Reporte actualizado exitosamente.');
    }
}