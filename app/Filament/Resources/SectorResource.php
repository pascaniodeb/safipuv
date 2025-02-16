<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectorResource\Pages;
use App\Filament\Resources\SectorResource\RelationManagers;
use App\Models\Sector;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\SecretaryNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SectorResource extends Resource
{
    use SecretaryNationalAccess;
    
    protected static ?string $model = Sector::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sectores';
    }

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 3; // Orden

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
                Forms\Components\Select::make('region_id')
                    ->label('Región')
                    ->relationship('region', 'name') // Relación con el modelo Region
                    ->preload() // Pre-carga las opciones
                    ->required()
                    ->live(), // Hace que el campo sea reactivo al cambio

                Forms\Components\Select::make('district_id')
                    ->label('Distrito')
                    ->options(function (callable $get) {
                        $regionId = $get('region_id'); // Obtiene el valor seleccionado en región
                        if ($regionId) {
                            return \App\Models\District::where('region_id', $regionId)
                                ->pluck('name', 'id'); // Filtra distritos según la región seleccionada
                        }
                        return [];
                    })
                    ->searchable() // Permite buscar dentro de las opciones
                    ->reactive() // Actualiza las opciones dinámicamente
                    ->required()
                    ->disabled(fn (callable $get) => !$get('region_id')) // Desactiva el select si no hay región seleccionada
                    ->placeholder('Seleccione una región primero'), // Mensaje de ayuda

                Forms\Components\TextInput::make('name')
                    ->label('Nombre del sector')
                    ->maxLength(100)
                    ->required(),

                Forms\Components\TextInput::make('number')
                    ->label('Número')
                    ->numeric()
                    ->required(),

                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('row_number') // Numerador de filas
                    ->label('Nro') // Etiqueta de la columna
                    ->rowIndex() // Activa el índice de la fila
                    ->sortable(), // Opcional: Permite ordenar
                Tables\Columns\TextColumn::make('district.region.name')
                    ->label('Región') // Muestra el nombre de la región
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('Distrito') // Muestra el nombre del distrito
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Sector') // Nombre del sector
                    ->searchable(),

                Tables\Columns\TextColumn::make('number')
                    ->label('Número')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Activo') // Estado del sector
                    ->boolean(),

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
                Tables\Filters\SelectFilter::make('region_id')
                    ->label('Región')
                    ->relationship('district.region', 'name') // Relación hacia el nombre de la región
                    ->searchable() // Permite buscar en el filtro
                    ->preload(), // Precarga las opciones para un mejor rendimiento

                Tables\Filters\SelectFilter::make('district_id')
                    ->label('Distrito')
                    ->relationship('district', 'name') // Relación hacia el nombre del distrito
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activo')
                    ->placeholder('Todos') // Texto por defecto
                    ->trueLabel('Sí')
                    ->falseLabel('No'),
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
            'index' => Pages\ListSectors::route('/'),
            'create' => Pages\CreateSector::route('/create'),
            'edit' => Pages\EditSector::route('/{record}/edit'),
        ];
    }
}