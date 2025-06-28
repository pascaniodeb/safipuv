<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Filament\Resources\CityResource\RelationManagers;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\RestrictToAdmin;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CityResource extends Resource
{
    use RestrictToAdmin;
    
    protected static ?string $model = City::class;

    public static function getPluralModelLabel(): string
    {
        return 'Ciudades';
    }

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 5; // Orden

    public static function getNavigationBadge(): ?string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::$model;

        return (string) $modelClass::count(); // Personaliza según sea necesario
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            

                Forms\Components\Select::make('state_id')
                    ->label('Estado') // Etiqueta del campo
                    ->relationship('state', 'name') // Relación y campo a mostrar
                    ->required()
                    ->preload() // Pre-carga los datos para un rendimiento más rápido
                    ->searchable() // Permite buscar en la lista de regiones
                    ->placeholder('Seleccione un estado'), // Texto por defecto
                    

                Forms\Components\TextInput::make('name')
                    ->label('Nombre del municipio')
                    ->maxLength(100)
                    ->required(),

                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([


                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado') // Muestra el nombre del distrito
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Municipio') // Nombre del sector
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado en')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado en')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country_id')
                    ->label('País')
                    ->relationship('state.country', 'name') // Relación hacia el nombre de la región
                    ->searchable() // Permite buscar en el filtro
                    ->preload(), // Precarga las opciones para un mejor rendimiento

                Tables\Filters\SelectFilter::make('state_id')
                    ->label('Estado')
                    ->relationship('state', 'name') // Relación hacia el nombre del distrito
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}