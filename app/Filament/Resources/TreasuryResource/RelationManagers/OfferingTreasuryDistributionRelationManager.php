<?php

namespace App\Filament\Resources\TreasuryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferingTreasuryDistributionRelationManager extends RelationManager
{
    protected static string $relationship = 'distributions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return 'Porcentajes'; // Título personalizado del encabezado
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Selección de la ofrenda
                Forms\Components\Select::make('offering_id')
                    ->label('Ofrenda')
                    ->relationship('offering', 'name') // Relación con el modelo Offering
                    ->required()
                    ->searchable() // Permite buscar entre las opciones
                    ->placeholder('Seleccione una ofrenda')
                    ->helperText('Seleccione la ofrenda para asociar esta deducción.'),

                // Selección de la tesorería
                Forms\Components\Select::make('treasury_id')
                    ->label('Tesorería')
                    ->relationship('treasury', 'name') // Relación con el modelo Treasury
                    ->required()
                    ->searchable() // Permite buscar entre las opciones
                    ->placeholder('Seleccione una tesorería')
                    ->helperText('Seleccione la tesorería para esta deducción.'),

                // Porcentaje
                Forms\Components\TextInput::make('percentage')
                    ->label('Porcentaje')
                    ->required()
                    ->numeric()
                    ->minValue(0) // Valor mínimo
                    ->maxValue(100) // Valor máximo
                    ->placeholder('Ingrese un porcentaje')
                    ->helperText('Ingrese el porcentaje de deducción (0-100).')
                    ->suffix('%'), // Agrega un sufijo visual

                // Relación con las subdivisiones
                Forms\Components\HasManyRepeater::make('subdivisions')
                    ->relationship('subdivisions') // Relación en el modelo
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Subdivisión')
                            ->required(),

                        Forms\Components\TextInput::make('percentage')
                            ->label('Porcentaje de Subdivisión')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                    ])
                    ->label('Subdivisiones')
                    ->createItemButtonLabel('Agregar Subdivisión')
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $totalPercentage = collect($state ?? [])->sum('percentage');

                        if ($totalPercentage > $get('percentage')) {
                            throw ValidationException::withMessages([
                                'subdivisions' => 'El porcentaje total de las subdivisiones no puede superar el porcentaje de distribución.',
                            ]);
                        }
                    }),
            ]);
    }


    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('treasury.name')
            ->columns([
                Tables\Columns\TextColumn::make('treasury.name')
                    ->label('Tesorería'),
                
                Tables\Columns\TextColumn::make('offering.name')
                    ->label('Ofrenda'),
                
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Porcentaje'),
                
                Tables\Columns\TextColumn::make('subdivisions')
                    ->label('Sub-Deducciones')
                    ->formatStateUsing(function ($record) {
                        return $record->subdivisions->map(function ($subdivision) {
                            return "{$subdivision->name}: {$subdivision->percentage}%";
                        })->implode(', ');
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Deducción')
                    ->modalHeading('Deducción de Tesorería'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}