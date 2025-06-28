<?php

namespace App\Filament\Resources\ChurchResource\Pages;

use App\Filament\Resources\ChurchResource;
use App\Models\Church;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditChurch extends EditRecord
{
    protected static string $resource = ChurchResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['code_church']) && isset($data['date_opening'])) {
            // Solo lo genera si no tiene código
            $date = Carbon::parse($data['date_opening']);
            $month = str_pad($date->format('m'), 2, '0', STR_PAD_LEFT);
            $year = $date->format('Y');
            $baseCode = "M{$month}A{$year}C";

            $nextIncrement = 1;

            do {
                $incrementPart = str_pad($nextIncrement, 4, '0', STR_PAD_LEFT);
                $code = $baseCode . $incrementPart;

                $exists = Church::withTrashed()
                    ->where('code_church', $code)
                    ->exists();

                $nextIncrement++;
            } while ($exists);

            $data['code_church'] = $code;
        }

        return $data;
    }



    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->hasAnyRole([
                    'Administrador',
                    'Secretario Nacional',
                    'Tesorero Nacional',
                ])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Editar Iglesia';
    }

    protected function beforeSave(): void
    {
        /** @var \App\Models\Church $church */
        $church = $this->record;

        // Recuperar datos originales
        $original = $church->fresh();

        // Detectar cambios de ubicación
        $cambioRegion = $original->region_id !== $church->region_id;
        $cambioDistrito = $original->district_id !== $church->district_id;
        $cambioSector = $original->sector_id !== $church->sector_id;

        if ($cambioRegion || $cambioDistrito || $cambioSector) {
            $mensaje = 'Has cambiado la ubicación geográfica de esta iglesia: ';
            if ($cambioRegion) {
                $mensaje .= '**Región**, ';
            }
            if ($cambioDistrito) {
                $mensaje .= '**Distrito**, ';
            }
            if ($cambioSector) {
                $mensaje .= '**Sector**, ';
            }

            // Quitar la última coma
            $mensaje = rtrim($mensaje, ', ') . '.';

            // Notificar al usuario actual
            Notification::make()
                ->title('Advertencia de cambio de ubicación')
                ->icon('heroicon-o-exclamation-triangle')
                ->body($mensaje)
                ->warning()
                ->send();
        }
    }


    /**
     * Notificar después de guardar cambios en la iglesia.
     */
    protected function afterSave(): void
    {
        /** @var Church $church */
        $church = $this->record;
        $usuario = auth()->user();

        // Toast + campanita al usuario actual
        Notification::make()
            ->title('Iglesia actualizada')
            ->icon('heroicon-o-pencil-square')
            ->body("La iglesia **{$church->name}** ha sido actualizada correctamente.")
            ->actions([
                Action::make('Ver')
                    ->url(ChurchResource::getUrl('edit', ['record' => $church]))
                    ->button()
                    ->openUrlInNewTab(),
            ])
            ->warning()
            ->sendToDatabase($usuario)
            ->send();

        // Notificar a los roles clave (campanita solamente)
        User::role([
            'Administrador',
            'Secretario Nacional',
            'Obispo Presidente',
            'Tesorero Nacional',
        ])->each(function (User $user) use ($church) {
            Notification::make()
                ->title('Iglesia modificada')
                ->icon('heroicon-o-pencil-square')
                ->body("La iglesia **{$church->name}** ha sido editada.")
                ->actions([
                    Action::make('Ver')
                        ->url(ChurchResource::getUrl('edit', ['record' => $church]))
                        ->button()
                        ->openUrlInNewTab(),
                ])
                ->info()
                ->sendToDatabase($user);
        });
    }
}