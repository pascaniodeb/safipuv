<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PastorResource\Pages;
use App\Filament\Resources\PastorResource\RelationManagers;
use App\Services\CarnetService;
use Filament\Notifications\Notification;
use App\Traits\SecretaryNationalAccess;
use App\Models\Pastor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\PastorsAccess;
use Filament\Tables;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CarnetResource extends Resource
{
    use SecretaryNationalAccess;
    
    protected static ?string $model = Pastor::class;

    protected static ?int $navigationSort = 4; // Orden

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Licencias';
    }
    
    protected static ?string $pluralModelLabel = 'Carnets';
    protected static ?string $modelLabel = 'Carnet';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('lastname')->label('Apellido')->searchable()->sortable(),
                TextColumn::make('number_cedula')->label('Cédula')->searchable()->sortable(),
                TextColumn::make('pastorMinistry.current_position.name')
                    ->label('Cargo Actual')
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generateCarnet')
                ->icon('heroicon-o-identification') // Ícono tipo carnet
                ->modalHeading('Generar Carnet')
                ->modalSubheading('¿Estás seguro de que deseas generar el carnet para este pastor?')
                ->modalButton('Generar')
                ->hidden(fn () => !in_array(auth()->user()->role, [
                    'Obispo Presidente',
                    'Secretario Nacional',
                    
                    'Administrador',
                ]))
                ->action(function (Pastor $record) {
                    // Instancia el servicio
                    $carnetService = app(\App\Services\CarnetService::class);

                    try {
                        $result = $carnetService->generateCarnet($record);

                        if (!empty($result)) {
                            // Crear un archivo ZIP
                            $zipFilePath = storage_path("app/public/carnets/{$record->number_cedula}_carnets.zip");
                            $zip = new ZipArchive();
            
                            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                                // Agregar los carnets al ZIP
                                $zip->addFile(storage_path("app/public/carnets/{$record->number_cedula}_carnet_front.png"), "carnet_front.png");
                                $zip->addFile(storage_path("app/public/carnets/{$record->number_cedula}_carnet_back.png"), "carnet_back.png");
                                $zip->close();
            
                                // Generar notificación con el enlace al ZIP
                                Notification::make()
                                    ->title('El carnet se generó exitosamente.')
                                    ->body("
                                        <p>Descarga el archivo con los carnets generados:</p>
                                        <a href='" . Storage::url("carnets/{$record->number_cedula}_carnets.zip") . "' target='_blank'>Descargar Carnets</a>
                                    ")
                                    ->success()
                                    ->send();
                            } else {
                                throw new \Exception("No se pudo crear el archivo ZIP.");
                            }
                        } else {
                            Notification::make()
                                ->title('Error al generar el carnet.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Hubo un error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarnets::route('/'),
        ];
    }
}