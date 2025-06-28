<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\TreasurerNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class ExchangeRateResource extends Resource
{
    use TreasurerNationalAccess;

    public static function canCreate(): bool
    {
        return false; // 🔴 Evita que se muestre el botón "Crear"
    }
    
    protected static ?string $model = ExchangeRate::class;
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tasas Generales';
    }

    public static function getModelLabel(): string
    {
        return 'Tasa General';
    }

    // ✅ FILTRO PRINCIPAL: Solo mostrar tasas generales al Tesorero Nacional
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('sector_id') // Solo tasas generales
            ->whereNull('month')     // Solo tasas generales (sin mes específico)
            ->whereIn('operation', ['=', '/', '*']); // Solo operaciones básicas
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✅ Mejorar el campo de moneda - Solo lectura en edición
                Forms\Components\TextInput::make('currency')
                    ->label('Moneda')
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->helperText(fn (string $operation): string => 
                        $operation === 'edit' 
                            ? 'La moneda no se puede modificar en registros existentes.'
                            : 'Seleccione la moneda base.'
                    ),

                // ✅ Campo principal - Tasa de cambio
                Forms\Components\TextInput::make('rate_to_bs')
                    ->label('Tasa de Cambio (Bs)')
                    ->required()
                    ->numeric()
                    ->step(0.000001) // Permitir hasta 6 decimales
                    ->minValue(0.000001)
                    ->maxValue(999999.999999)
                    ->placeholder('Ejemplo: 97.310000')
                    ->helperText('Ingrese la tasa de cambio oficial del día.')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $get) {
                        if ($state && $get('currency')) {
                            $currency = $get('currency');
                            $operation = $get('operation');
                            $formatted = number_format((float)$state, 2, ',', '.');
                            
                            $operationText = match($operation) {
                                '=' => 'Igual a',
                                '/' => 'División', 
                                '*' => 'Multiplicación',
                                default => $operation
                            };
                            
                            Notification::make()
                                ->title('Vista previa')
                                ->body("{$currency}: Bs {$formatted} ({$operationText})")
                                ->info()
                                ->duration(3000)
                                ->send();
                        }
                    }),

                // ✅ Operación - Solo lectura en edición
                Forms\Components\Select::make('operation')
                    ->label('Tipo de Operación')
                    ->disabled()
                    ->options([
                        '=' => 'Igual a (=) - Para Bolívares',
                        '/' => 'División (÷) - Para Dólares',
                        '*' => 'Multiplicación (×) - Para Pesos Colombianos',
                    ])
                    ->helperText(fn (string $operation): string => 
                        $operation === 'edit' 
                            ? 'La operación no se puede modificar en registros existentes.'
                            : 'Seleccione el tipo de operación matemática.'
                    ),

                // ✅ Información contextual
                Forms\Components\Placeholder::make('conversion_info')
                    ->label('Información de Conversión')
                    ->content(function ($get) {
                        $currency = $get('currency');
                        $operation = $get('operation');
                        $rate = $get('rate_to_bs');

                        if (!$currency || !$operation) {
                            return 'Seleccione moneda y operación para ver información.';
                        }

                        $currencyName = match($currency) {
                            'USD' => '🇺🇸 Dólares estadounidenses',
                            'COP' => '🇨🇴 Pesos colombianos', 
                            'VES' => '🇻🇪 Bolívares venezolanos',
                            default => $currency
                        };

                        $operationExplanation = match($operation) {
                            '*' => 'Los montos en COP se multiplican por la tasa para convertir COP a Bolívares',
                            '/' => 'Los montos en USD se dividen por la tasa para convertir USD a Bolívares',
                            '=' => 'Los montos en bolívares mantienen su valor (1:1)',
                            default => "Operación: {$operation}"
                        };

                        $example = '';
                        if ($rate && $rate > 0) {
                            $rateFormatted = number_format((float)$rate, 2, ',', '.');
                            $example = match($operation) {
                                '*' => " Ejemplo: 1 COP × {$rateFormatted} = Bs {$rateFormatted}",
                                '/' => " Ejemplo: Bs {$rateFormatted} ÷ {$rateFormatted} = $1 USD",
                                '=' => " Ejemplo: Bs 1,00 = Bs 1,00",
                                default => ''
                            };
                        }

                        return "{$currencyName} {$operationExplanation} {$example}";
                    })
                    ->columnSpanFull(),

                // Campos ocultos para asegurar que se guarden como tasas generales
                Forms\Components\Hidden::make('sector_id')->default(null),
                Forms\Components\Hidden::make('month')->default(null),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Tasas de Cambio Generales')
            ->description('Gestione las tasas oficiales que se aplicarán en todo el sistema.')
            ->columns([
                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'VES' => 'primary',
                        'USD' => 'success',
                        'COP' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'USD' => 'heroicon-m-currency-dollar',
                        'COP' => 'heroicon-m-currency-dollar',
                        'VES' => 'heroicon-m-banknotes',
                        default => 'heroicon-m-currency-dollar',
                    }),

                Tables\Columns\TextColumn::make('rate_to_bs')
                    ->label('Tasa de Cambio')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 6, ',', '.'))
                    ->copyable()
                    ->copyMessage('Tasa copiada al portapapeles')
                    ->copyMessageDuration(2000)
                    ->description(fn ($record) => 
                        "Actualizada " . $record->updated_at->diffForHumans()
                    ),

                Tables\Columns\TextColumn::make('operation')
                    ->label('Operación')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        '=' => '= Igual a',
                        '/' => '÷ División',
                        '*' => '× Multiplicación',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '=' => 'primary',
                        '/' => 'success',
                        '*' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->updated_at->format('l, j \\d\\e F \\d\\e Y')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Filtrar por Moneda')
                    ->options([
                        'VES' => 'VES - Bolívares',
                        'USD' => 'USD - Dólares',
                        'COP' => 'COP - Pesos',
                    ]),

                Tables\Filters\SelectFilter::make('operation')
                    ->label('Filtrar por Operación')
                    ->options([
                        '=' => 'Igual a (=)',
                        '/' => 'División (÷)',
                        '*' => 'Multiplicación (×)',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar Tasa')
                    ->modalHeading(fn ($record) => "Editar Tasa de {$record->currency}")
                    ->modalDescription(fn ($record) => 
                        "Modifique la tasa de cambio para {$record->currency}. Solo se puede cambiar el valor."
                    )
                    ->modalWidth('md')
                    ->successNotificationTitle('Tasa actualizada correctamente')
                    ->after(function ($record) {
                        // Limpiar cache después de editar
                        Cache::forget('dashboard_exchange_rates');
                        Cache::forget('exchange_stats');
                        
                        $rate = number_format($record->rate_to_bs, 2, ',', '.');
                        
                        Notification::make()
                            ->title('Sistema actualizado')
                            ->body("Nueva tasa {$record->currency}: Bs {$rate}")
                            ->success()
                            ->duration(4000)
                            ->send();
                    }),
            ])
            // ✅ Remover acciones de eliminación para proteger los datos
            ->bulkActions([])
            ->defaultSort('currency', 'asc')
            ->emptyStateHeading('No hay tasas generales')
            ->emptyStateDescription('Contacte al administrador del sistema.')
            ->emptyStateIcon('heroicon-o-exclamation-triangle')
            ->striped()
            ->paginated(false); // Sin paginación para ver las 3 tasas siempre
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExchangeRates::route('/'),
        ];
    }

    // ✅ Validación adicional al editar
    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Asegurar que siempre sean tasas generales
        $data['sector_id'] = null;
        $data['month'] = null;
        
        return $data;
    }

    // ✅ Búsqueda global
    public static function getGloballySearchableAttributes(): array
    {
        return ['currency'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNull('sector_id')
                                ->whereNull('month')
                                ->whereIn('operation', ['=', '/', '*'])
                                ->count();
    }
}