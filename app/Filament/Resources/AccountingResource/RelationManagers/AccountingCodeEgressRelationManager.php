<?php

namespace App\Filament\Resources\AccountingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingCodeEgressRelationManager extends RelationManager
{
    protected static string $relationship = 'codes';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return 'Códigos de Egreso'; // Título personalizado del encabezado
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Relación con la contabilidad
                Forms\Components\Select::make('accounting_id')
                    ->label('Contabilidad')
                    ->relationship('accounting', 'name') // Relación con el modelo Accounting
                    ->required()
                    ->searchable(),

                // Relación opcional con roles
                Forms\Components\Select::make('role_id')
                    ->label('Rol')
                    ->relationship('role', 'name') // Relación con el modelo de Roles (si existe)
                    ->searchable()
                    ->nullable(),

                // Relación opcional con movimientos contables
                Forms\Components\Select::make('movement_id')
                    ->label('Movimiento Contable')
                    ->relationship('movement', 'type') // Relación con el modelo Movement
                    ->searchable()
                    ->nullable(),

                // Campo para el código contable
                Forms\Components\TextInput::make('code')
                    ->label('Código Contable')
                    ->required()
                    ->maxLength(8)
                    ->placeholder('Ejemplo: C12345'),

                // Campo para la descripción
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->maxLength(50)
                    ->placeholder('Descripción del código contable'),

                // Campo para el estado
                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columna para la contabilidad
                Tables\Columns\TextColumn::make('accounting.name')
                    ->label('Contabilidad')
                    ->sortable()
                    ->searchable(),

                // Columna para el rol (opcional)
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Rol')
                    ->searchable(),

                // Columna para el movimiento contable
                Tables\Columns\BadgeColumn::make('movement.type')
                    ->label('Movimiento Contable')
                    ->colors([
                        'success' => fn ($state) => $state === 'Ingreso', // Verde para ingresos
                        'warning' => fn ($state) => $state === 'Egreso',  // Naranja para egresos
                    ])
                    ->icons([
                        'heroicon-o-plus-circle' => fn ($state) => $state === 'Ingreso', // Ícono para ingresos
                        'heroicon-o-minus-circle' => fn ($state) => $state === 'Egreso', // Ícono para egresos
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)) // Capitaliza el texto del estado
                    ->searchable(),


                // Columna para el código contable
                Tables\Columns\TextColumn::make('code')
                    ->label('Código Contable')
                    ->sortable()
                    ->searchable(),

                // Columna para la descripción
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción'),

                // Columna para el estado
                Tables\Columns\BooleanColumn::make('active')
                    ->label('Activo'),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Solo Activos')
                    ->query(fn (Builder $query) => $query->where('active', true)),
            
                Tables\Filters\Filter::make('egresos')
                    ->label('Egreso')
                    ->query(fn (Builder $query) => $query->whereRelation('movement', 'type', 'Egreso')) // Filtra los egresos
                    ->default(), // Aplica este filtro por defecto
            ])
            
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Código')
                    ->modalHeading('Código Contable'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}