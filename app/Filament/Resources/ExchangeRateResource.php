<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Filament\Resources\ExchangeRateResource\RelationManagers;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\TreasurerNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExchangeRateResource extends Resource
{
    use TreasurerNationalAccess;

    public static function canCreate(): bool
    {
        return false; // 🔴 Evita que se muestre el botón "Crear"
    }
    
    protected static ?string $model = ExchangeRate::class;

    protected static ?int $navigationSort = 1; // Orden

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tasa de Cambio';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                
                Forms\Components\TextInput::make('currency')
                    ->label('Moneda')
                    ->required()
                    ->maxLength(3)
                    ->placeholder('USD, COP, VES')
                    ->disabled(fn ($state) => in_array($state, ['VES'])) // Deshabilitar para Bolívares
                    ->helperText('Ingrese el código de la moneda según el estándar ISO 4217.'),

                Forms\Components\TextInput::make('rate_to_bs')
                    ->label('Tasa de Cambio')
                    ->required()
                    ->numeric()
                    ->placeholder('Ejemplo: 53.00 para USD o 67.00 para COP')
                    ->helperText('Ingrese la tasa de cambio actual.'),

                Forms\Components\Select::make('operation')
                    ->label('Operación')
                    ->required()
                    ->options([
                        '=' => 'Igual a (Bolívares)',
                        '*' => 'Multiplicación (Dólares)',
                        '/' => 'División (Pesos Colombianos)',
                    ])
                    ->helperText('Seleccione la operación para convertir a Bolívares.'),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rate_to_bs')
                    ->label('Tasa de Cambio')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('operation')
                    ->label('Operación')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        '=' => 'Igual a',
                        '*' => 'Multiplicación',
                        '/' => 'División',
                    }),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Tasa de Cambio')
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExchangeRates::route('/'),
        ];
    }
}