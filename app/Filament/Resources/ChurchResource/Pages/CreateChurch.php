<?php

namespace App\Filament\Resources\ChurchResource\Pages;

use App\Filament\Resources\ChurchResource;
use Filament\Forms;
use Illuminate\Support\Carbon;
use App\Models\Church;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Section;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateChurch extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = ChurchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! isset($data['date_opening'])) {
            return $data;
        }

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

        return $data;
    }


    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                Wizard::make($this->getSteps())
                    ->startOnStep($this->getStartStep())
                    ->cancelAction($this->getCancelFormAction())
                    ->submitAction($this->getSubmitFormAction())
                    ->skippable(false)
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Datos Fundacionales')
                ->schema([
                    Section::make()
                        ->schema(ChurchResource::getStepFundacionales())
                        ->columns(['default' => 1, 'md' => 2]),
                        
                ]),

            Step::make('Datos de Membresía')
                ->schema([
                    Section::make()
                        ->schema(ChurchResource::getStepMembresia())
                        ->columns(['default' => 1, 'md' => 3]),
                ]),

            Step::make('Datos del Pastor')
                ->schema([
                    Section::make()
                        ->schema(ChurchResource::getStepPastor())
                        ->columns(['default' => 1, 'md' => 3]),
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Nueva Iglesia';
    }

    protected function afterCreate(): void
    {
        /** @var \App\Models\Church $church */
        $church = $this->record;

        /** @var \App\Models\User $creador */
        $creador = auth()->user();

        // ✅ Toast + Campanita para el usuario creador
        Notification::make()
            ->title('Iglesia registrada exitosamente')
            ->icon('heroicon-o-building-library')
            ->body("La iglesia **{$church->name}** fue creada con el código **{$church->code_church}**.")
            ->actions([
                Action::make('Ver Iglesia')
                    ->url(ChurchResource::getUrl('edit', ['record' => $church]))
                    ->button()
                    ->openUrlInNewTab(),
            ])
            ->success()
            ->sendToDatabase($creador)
            ->send(); // ✅ Esto ya está correcto

        // ✅ Notificación a roles clave (campanita), evitando repetir al creador
        User::role([
            'Administrador',
            'Secretario Nacional',
            'Obispo Presidente',
            'Tesorero Nacional',
        ])->each(function (User $user) use ($church, $creador) {
            // Evita notificar dos veces al creador
            if ($user->is($creador)) {
                return;
            }

            Notification::make()
                ->title('Nueva iglesia registrada')
                ->icon('heroicon-o-building-library')
                ->body("Se ha creado la iglesia **{$church->name}** con el código **{$church->code_church}**.")
                ->actions([
                    Action::make('Ver')
                        ->url(ChurchResource::getUrl('edit', ['record' => $church]))
                        ->button()
                        ->openUrlInNewTab(),
                ])
                ->info()
                ->sendToDatabase($user)
                ->send(); // ⚠️ ESTO FALTABA - Agregar send() aquí también
        });
    }
    
}