<?php

namespace App\Filament\Resources\ChurchResource\Pages;

use App\Filament\Resources\ChurchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListChurches extends ListRecords
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();

        // 🔹 Definir los roles que pueden ver el botón de exportación
        $allowedRoles = [
            'Administrador', 'Obispo Presidente', 'Secretario Nacional', 
            'Tesorero Nacional', 'Contralor Nacional', 'Inspector Nacional', 
            'Directivo Nacional', // nationalRoles

            'Superintendente Regional', 'Secretario Regional', 
            'Tesorero Regional', 'Contralor Regional', 'Inspector Regional', 
            'Directivo Regional', // regionalRoles

            'Supervisor Distrital', // districtRoles

            'Presbítero Sectorial', 'Secretario Sectorial', 'Tesorero Sectorial', 
            'Contralor Sectorial', 'Directivo Sectorial' // sectorRoles
        ];

        $actions = [
            Actions\CreateAction::make()
                ->label('Nueva Iglesia'),
        ];

        if ($user->hasAnyRole($allowedRoles)) {
            $actions[] = Actions\Action::make('Exportar Excel')
                ->label('Exportar a Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->url(route('export.churches.excel'))
                ->openUrlInNewTab()
                ->color('success');
        }

        return $actions;
    }
}