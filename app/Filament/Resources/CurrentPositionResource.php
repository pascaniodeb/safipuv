<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrentPositionResource\Pages;
use App\Filament\Resources\CurrentPositionResource\RelationManagers;
use App\Models\CurrentPosition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\SecretaryNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CurrentPositionResource extends Resource
{
    use SecretaryNationalAccess;
    
    protected static ?string $model = CurrentPosition::class;

    protected static ?int $navigationSort = 5; // Orden

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cargos';
    }

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
                Forms\Components\Section::make('Detalles del Cargo')
                    ->schema([
                        Forms\Components\Grid::make(2) // Organiza en 2 columnas
                            ->schema([
                                Forms\Components\Select::make('position_type_id')
                                    ->label('Tipo de Cargo')
                                    ->relationship('positionType', 'name') // Asegúrate de tener la relación configurada
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Seleccione un tipo de cargo'),

                                Forms\Components\Select::make('gender_id')
                                    ->label('Género')
                                    ->relationship('gender', 'name') // Asegúrate de tener la relación configurada
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Seleccione un género'),
                            ]),
                    ]),

                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ingrese el nombre'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ingrese una descripción'),
                    ])
                    ->collapsible(), // Permite colapsar la sección para ahorrar espacio
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('positionType.name')
                    ->label('Tipo de Posición')
                    ->sortable()
                    ->searchable(), // Habilitar búsqueda en esta columna

                // Columna para Género con relación
                Tables\Columns\TextColumn::make('gender.name')
                    ->label('Género')
                    ->sortable()
                    ->searchable(), // Habilitar búsqueda en esta columna

                // Columna para Nombre
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->wrap(), // Permite que el texto se ajuste si es muy largo

                // Columna para Descripción
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap() // Permite que el texto se ajuste si es muy largo
                    ->toggleable() // Permite ocultar esta columna si es necesario
                    ->toggleable(isToggledHiddenByDefault: true),

                // Fecha de Creación
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i') // Personalizar el formato de fecha y hora
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculta por defecto pero puede activarse

                // Fecha de Actualización
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i') // Personalizar el formato de fecha y hora
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculta por defecto pero puede activarse
            ])
            ->filters([
                // Filtro por Tipo de Posición
                Tables\Filters\SelectFilter::make('position_type_id')
                    ->label('Tipo de Posición')
                    ->options(\App\Models\PositionType::pluck('name', 'id')) // Carga los nombres de los tipos de posición
                    ->searchable(), // Habilita la búsqueda en el filtro

                // Filtro por Género
                Tables\Filters\SelectFilter::make('gender_id')
                    ->label('Género')
                    ->options(\App\Models\Gender::pluck('name', 'id')) // Carga los nombres de los géneros
                    ->searchable(), // Habilita la búsqueda en el filtro

                // Filtro por Nombre
                Tables\Filters\Filter::make('name')
                    ->label('Nombre')
                    ->query(fn (Builder $query, $value) => $query->where('name', 'like', "%{$value}%")), // Filtro por búsqueda parcial
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
            'index' => Pages\ListCurrentPositions::route('/'),
            'create' => Pages\CreateCurrentPosition::route('/create'),
            'edit' => Pages\EditCurrentPosition::route('/{record}/edit'),
        ];
    }
}