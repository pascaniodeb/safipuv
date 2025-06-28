<?php

namespace App\Filament\Resources\PastorResource\Pages;

use App\Filament\Resources\PastorResource;
use App\Services\PastorAssignmentService;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPastor extends EditRecord
{
    protected static string $resource = PastorResource::class;

    protected function afterSave()
    {
        (new PastorAssignmentService)->assignLicenceAndLevel($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole([
                    'Administrador',
                    'Secretario Nacional',
                    'Tesorero Nacional',
                ]))
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        // Redirigir al índice del recurso
        return $this->getResource()::getUrl('index');
    }
    public function getTitle(): string
    {
        return 'Editar Pastor'; // Cambia el título aquí
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('Editar Pastor')
            ->body('Pastor actualizado exitosamente.');
    }
}