<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferingTransactionResource\Pages;
use App\Filament\Resources\OfferingTransactionResource\RelationManagers;
use App\Models\Offering;
use App\Models\OfferingReport;
use App\Models\OfferingItem;
use Illuminate\Support\Facades\Auth;
use App\Models\Pastor;
use App\Models\Church;
use App\Models\BankTransaction; // Modelo para la tabla bank_transactions
use App\Models\Bank; // Modelo para la tabla banks
use App\Models\OfferingTransaction;
use Filament\Notifications\Notification;
use App\Traits\RestrictToAdmin;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferingTransactionResource extends Resource
{
    use RestrictToAdmin;
    
    protected static ?string $model = OfferingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-s-pencil-square';

    public static function getPluralModelLabel(): string
    {
        return 'test-1';
    }

    

    public static function scopeAccessControlQuery(Builder $query): Builder
    {
        $user = Auth::user();

        // Verificar si el usuario está autenticado para evitar errores
        if (!$user) {
            return $query->whereRaw('1 = 0'); // Retorna una consulta vacía si no hay usuario
        }

        // Acceso completo para roles nacionales
        if ($user->hasAnyRole(self::$nationalRoles)) {
            return $query;
        }

        // Filtrar registros por región para roles regionales
        if ($user->hasAnyRole(self::$regionalRoles)) {
            return $query->where('region_id', $user->region_id);
        }

        // Filtrar registros por distrito
        if ($user->hasRole('Supervisor Distrital')) {
            return $query->where('district_id', $user->district_id);
        }

        // Filtrar registros por sector
        if ($user->hasAnyRole(self::$sectorRoles)) {
            return $query->where('sector_id', $user->sector_id);
        }

        // Si no tiene ninguno de los roles, restringir el acceso
        return $query->whereRaw('1 = 0'); // Retorna una consulta vacía
    }



    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Selección de Pastor
                Forms\Components\Section::make('Seleccionar Pastor')
                    ->schema([
                        Forms\Components\Select::make('pastor_id')
                            ->label('Seleccione un Pastor')
                            ->options(function () {
                                $user = auth()->user();

                                if (method_exists($user, 'hasRole')) {
                                    return match (true) {
                                        $user->hasRole('Tesorero Nacional') => \App\Models\Pastor::selectRaw("id, CONCAT(name, ' ', lastname) as full_name")->pluck('full_name', 'id'),
                                        $user->hasRole('Tesorero Regional') => \App\Models\Pastor::where('region_id', $user->region_id)
                                            ->selectRaw("id, CONCAT(name, ' ', lastname) as full_name")
                                            ->pluck('full_name', 'id'),
                                        $user->hasRole('Tesorero Sectorial') => \App\Models\Pastor::where('sector_id', $user->sector_id)
                                            ->selectRaw("id, CONCAT(name, ' ', lastname) as full_name")
                                            ->pluck('full_name', 'id'),
                                        default => [],
                                    };
                                }

                                return [];
                            })
                            ->searchable()
                            ->required(),


                        Forms\Components\Select::make('month')
                            ->label('Mes de Registro')
                            ->options(function () {
                                $today = \Carbon\Carbon::now();
                                $months = [];
                        
                                // Generar los últimos 3 meses
                                for ($i = 1; $i <= 3; $i++) {
                                    $month = $today->copy()->subMonthsNoOverflow($i);
                                    $months[$month->format('Y-m')] = $month->translatedFormat('F Y');
                                }
                        
                                return $months;
                            })
                            ->placeholder('Seleccione un mes') // Colocar un placeholder
                            ->required() // Hacer que el campo sea obligatorio
                            ->reactive() // Reactivo para cambios dinámicos
                            ->disabled(function () {
                                $user = auth()->user();
                        
                                // Solo deshabilitar para Tesoreros Sectoriales y Regionales después del día 10
                                return (
                                    ($user->hasRole('Tesorero Sectorial') || $user->hasRole('Tesorero Regional')) &&
                                    \Carbon\Carbon::now()->day > 15
                                );
                            })
                            ->visible(function () {
                                $user = auth()->user();
                        
                                // Hacer que el campo sea visible solo para los roles indicados
                                return $user->hasRole(['Tesorero Sectorial', 'Tesorero Regional', 'Tesorero Nacional']);
                            })
                            
                    ])
                    ->columns(2),

                // Tasas de Cambio
                Forms\Components\Section::make('Tasas de Cambio')
                    ->schema([
                        Forms\Components\TextInput::make('usd_rate')
                            ->label('Tasa USD a Bs')
                            ->default(fn () => \App\Models\ExchangeRate::where('currency', 'USD')->latest()->value('rate_to_bs') ?? 0)
                            ->disabled()
                            ->dehydrated(),
                            
                        Forms\Components\TextInput::make('cop_rate')
                            ->label('Tasa COP a Bs')
                            ->default(fn () => \App\Models\ExchangeRate::where('currency', 'COP')->latest()->value('rate_to_bs') ?? 0)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),
                
                
                Forms\Components\Section::make('Tasas de Cambio')
                    ->schema([
                        Forms\Components\Repeater::make('offeringItems')
                            ->label('Listado de Ofrendas')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('offering_id')
                                            ->label('Tipo de Ofrenda')
                                            ->options(\App\Models\Offering::all()->pluck('name', 'id'))
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $pastorId = $get('pastor_id');
                                                $month = $get('month');
                                                
                                                if ($pastorId && $month) {
                                                    $exists = \App\Models\OfferingTransaction::where('offering_id', $state)
                                                        ->where('pastor_id', $pastorId)
                                                        ->where('month', $month)
                                                        ->exists();
                                                    
                                                    if ($exists) {
                                                        // Notificar y no borrar el valor para evitar que se envíe nulo.
                                                        Notification::make()
                                                            ->title('Ofrenda duplicada')
                                                            ->body('Este tipo de ofrenda ya ha sido registrada para el mismo mes.')
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }
                                            }),

                                        Select::make('bank_transaction_id')
                                            ->label('Tipo de Transacción')
                                            ->placeholder('Seleccione...')
                                            ->options(BankTransaction::pluck('name', 'id'))
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $set) {
                                                $showFields = in_array($state, [1, 2]); // ID 1 y 2 muestran los campos
                                                $set('show_fields', $showFields);
                                            }),

                                        Hidden::make('show_fields') // Campo oculto para controlar la visibilidad
                                            ->default(false),

                                        Select::make('bank_id')
                                            ->label('Banco')
                                            ->placeholder('Seleccione un banco...')
                                            ->options(Bank::pluck('name', 'id'))
                                            ->visible(fn (callable $get) => $get('show_fields'))
                                            ->nullable(),

                                        DatePicker::make('transaction_date')
                                            ->label('Fecha de la Transacción')
                                            ->visible(fn (callable $get) => $get('show_fields'))
                                            ->nullable(),

                                        TextInput::make('transaction_number')
                                            ->label('Número de Transacción')
                                            ->visible(fn (callable $get) => $get('show_fields'))
                                            ->nullable(),
                                    ]),

                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('amount_bs')
                                            ->label('Monto en Bs')
                                            ->numeric()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                self::calculateSubtotal($set, $get);
                                                self::calculateGlobalTotals($set, $get);
                                            }),

                                        TextInput::make('amount_usd')
                                            ->label('Monto en USD')
                                            ->numeric()
                                            ->default(0.00)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                self::calculateSubtotal($set, $get);
                                                self::calculateGlobalTotals($set, $get);
                                            }),

                                        TextInput::make('amount_cop')
                                            ->label('Monto en COP')
                                            ->numeric()
                                            ->default(0.00)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                self::calculateSubtotal($set, $get);
                                                self::calculateGlobalTotals($set, $get);
                                            }),


                                        TextInput::make('subtotal_bs')
                                            ->label('Subtotal en Bs')
                                            ->numeric()
                                            ->default(0.00)
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->createItemButtonLabel('Agregar Ofrenda')
                            ->columns(1)
                    ]),
                    // Totales Globales
                    Forms\Components\Section::make('Totales Globales')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('total_bs')
                                    ->label('Total en Bs')
                                    ->numeric()
                                    ->reactive()
                                    ->live()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('total_usd')
                                    ->label('Total en Usd')
                                    ->numeric()
                                    ->reactive()
                                    ->live()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                                
                                Forms\Components\TextInput::make('total_cop')
                                    ->label('Total en Usd')
                                    ->numeric()
                                    ->reactive()
                                    ->live()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                                
                            ]),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('total_usd_to_bs')
                                        ->label('Total USD a Bs')
                                        ->numeric()
                                        ->reactive()
                                        ->default(0)
                                        ->disabled()
                                        ->dehydrated(),

                                    Forms\Components\TextInput::make('total_cop_to_bs')
                                        ->label('Total COP a Bs')
                                        ->numeric()
                                        ->reactive()
                                        ->default(0)
                                        ->disabled()
                                        ->dehydrated(),

                                    Forms\Components\TextInput::make('grand_total_bs')
                                        ->label('Gran Total en Bs')
                                        ->numeric()
                                        ->reactive()
                                        ->default(0)
                                        ->disabled()
                                        ->dehydrated(),
                                    
                                ]),
                            
                    ])
                    ->columns(4),
                
            ]);

            
            
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Nombre del Pastor (Relación)
                Tables\Columns\TextColumn::make('pastor')
                    ->label('Pastor')
                    ->formatStateUsing(function ($record) {
                        return "{$record->pastor->name} {$record->pastor->lastname}";
                    })
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('pastor', function ($query) use ($search) {
                            $query->whereRaw("CONCAT(name, ' ', lastname) LIKE ?", ["%{$search}%"]);
                        });
                    }),


                // Mes de la Transacción
                Tables\Columns\TextColumn::make('month')
                    ->label('Mes')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return null; // Manejar si el valor está vacío
                        }

                        // Convertir el mes y el año en el formato deseado
                        try {
                            $date = \Carbon\Carbon::createFromFormat('Y-m', $state);
                            return $date->translatedFormat('M-Y'); // Ejemplo: Ene-2024
                        } catch (\Exception $e) {
                            return $state; // Devuelve el valor original si hay error
                        }
                    })
                    ->sortable()
                    ->searchable(),

                
                // Tipo de Ofrenda (Relación con Nombre)
                Tables\Columns\TextColumn::make('offering.name')
                    ->label('Tipo de Ofrenda')
                    ->sortable()
                    ->searchable(),

                // Monto en Bs
                Tables\Columns\TextColumn::make('amount_bs')
                    ->label('Monto en Bs')
                    ->money('VES') // Formato de moneda para Bolívares
                    ->sortable(),

                // Monto en USD
                Tables\Columns\TextColumn::make('amount_usd')
                    ->label('Monto en USD')
                    ->money('USD') // Formato de moneda para USD
                    ->sortable(),

                // Monto en COP
                Tables\Columns\TextColumn::make('amount_cop')
                    ->label('Monto en COP')
                    ->money('COP') // Formato de moneda para COP
                    ->sortable(),

                // Subtotal en Bs
                Tables\Columns\TextColumn::make('subtotal_bs')
                    ->label('Subtotal en Bs')
                    ->money('VES')
                    ->sortable(),

                // Tasa USD a Bs
                Tables\Columns\TextColumn::make('usd_rate')
                    ->label('Tasa USD a Bs')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Tasa COP a Bs
                Tables\Columns\TextColumn::make('cop_rate')
                    ->label('Tasa COP a Bs')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Total en Bs
                Tables\Columns\TextColumn::make('total_bs')
                    ->label('Total en Bs')
                    ->money('VES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Total USD Convertido a Bs
                Tables\Columns\TextColumn::make('total_usd_to_bs')
                    ->label('Total USD a Bs')
                    ->money('VES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Total COP Convertido a Bs
                Tables\Columns\TextColumn::make('total_cop_to_bs')
                    ->label('Total COP a Bs')
                    ->money('VES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Gran Total en Bs
                Tables\Columns\TextColumn::make('grand_total_bs')
                    ->label('Gran Total en Bs')
                    ->money('VES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Fecha de Creación
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime('d-m-Y H:i') // Formato de fecha
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Fecha de Actualización
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime('d-m-Y H:i') // Formato de fecha
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            //->defaultSort('offering_id', 'asc') // Ordenar por el ID de la ofrenda en orden ascendente


            ->filters([
                Tables\Filters\Filter::make('top_pastors')
                    ->label('Top 200 Pastores con Mayores Montos')
                    ->form([
                        Forms\Components\Select::make('period')
                            ->label('Periodo')
                            ->options([
                                'month' => 'Por Mes',
                                'quarter' => 'Por Trimestre',
                                'semester' => 'Por Semestre',
                                'year' => 'Por Año',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('value')
                            ->label('Valor del Periodo')
                            ->hint('Ejemplo: 2024-01 (Mes), 2024-Q1 (Trimestre), 2024-H1 (Semestre), 2024 (Año)')
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['period']) || empty($data['value'])) {
                            return;
                        }

                        $period = $data['period'];
                        $value = $data['value'];

                        switch ($period) {
                            case 'month':
                                $query->where('month', '=', $value);
                                break;

                            case 'quarter':
                                // Extraer año y trimestre (Ejemplo: 2024-Q1)
                                [$year, $quarter] = explode('-Q', $value);
                                $months = match ($quarter) {
                                    '1' => ['01', '02', '03'], // Primer trimestre
                                    '2' => ['04', '05', '06'], // Segundo trimestre
                                    '3' => ['07', '08', '09'], // Tercer trimestre
                                    '4' => ['10', '11', '12'], // Cuarto trimestre
                                    default => [],
                                };
                                $query->whereYear('month', $year)->whereMonth('month', $months);
                                break;

                            case 'semester':
                                // Extraer año y semestre (Ejemplo: 2024-H1)
                                [$year, $semester] = explode('-H', $value);
                                $months = match ($semester) {
                                    '1' => ['01', '02', '03', '04', '05', '06'], // Primer semestre
                                    '2' => ['07', '08', '09', '10', '11', '12'], // Segundo semestre
                                    default => [],
                                };
                                $query->whereYear('month', $year)->whereMonth('month', $months);
                                break;

                            case 'year':
                                // Filtrar solo por año
                                $query->whereYear('month', '=', $value);
                                break;
                        }

                        // Agrupar por pastor, sumar los subtotales y ordenar por mayor monto
                        $query->selectRaw('pastor_id, SUM(subtotal_bs) as total_bs')
                            ->groupBy('pastor_id')
                            ->orderBy('total_bs', 'desc')
                            ->limit(200);
                    }),
                
                // Filtro por Tipo de Ofrenda
                Tables\Filters\SelectFilter::make('offering_id')
                    ->label('Tipo de Ofrenda')
                    ->options(\App\Models\Offering::all()->pluck('name', 'id')),
            
                // Filtro por Pastor
                Tables\Filters\SelectFilter::make('pastor_id')
                    ->label('Pastor')
                    ->options(\App\Models\Pastor::all()->pluck('name', 'id'))
                    ->searchable(),
            
                // Filtro por Mes
                Tables\Filters\Filter::make('month')
                    ->label('Mes')
                    ->form([
                        Forms\Components\DatePicker::make('month')
                            ->label('Seleccione un Mes')
                            ->format('Y-m'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['month'])) {
                            $query->where('month', '=', \Carbon\Carbon::parse($data['month'])->format('Y-m'));
                        }
                    }),
            
                // Filtro por Monto en Bs (Rango)
                Tables\Filters\Filter::make('amount_bs')
                    ->label('Monto en Bs')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Mínimo')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Máximo')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['min_amount'])) {
                            $query->where('amount_bs', '>=', $data['min_amount']);
                        }
                        if (!empty($data['max_amount'])) {
                            $query->where('amount_bs', '<=', $data['max_amount']);
                        }
                    }),
            
                // Filtro por Año
                Tables\Filters\SelectFilter::make('year')
                    ->label('Año')
                    ->options(
                        \App\Models\OfferingTransaction::selectRaw('DISTINCT YEAR(STR_TO_DATE(CONCAT(month, "-01"), "%Y-%m-%d")) as year')
                            ->pluck('year', 'year')
                            ->toArray()
                    ),
            
                // Filtro por Fecha de Creación (Rango)
                Tables\Filters\Filter::make('created_at')
                    ->label('Fecha de Registro')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['start_date'])) {
                            $query->whereDate('created_at', '>=', $data['start_date']);
                        }
                        if (!empty($data['end_date'])) {
                            $query->whereDate('created_at', '<=', $data['end_date']);
                        }
                    }),
            
                // Filtro por Gran Total (Rango)
                Tables\Filters\Filter::make('grand_total_bs')
                    ->label('Gran Total en Bs')
                    ->form([
                        Forms\Components\TextInput::make('min_total')
                            ->label('Mínimo')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_total')
                            ->label('Máximo')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['min_total'])) {
                            $query->where('grand_total_bs', '>=', $data['min_total']);
                        }
                        if (!empty($data['max_total'])) {
                            $query->where('grand_total_bs', '<=', $data['max_total']);
                        }
                    }),
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

    

    // Fuera del método getItemsRepeater (en tu OfferingReporteResource):
    public static function calculateSubtotal(callable $set, callable $get)
    {
        $amountBs = $get('amount_bs') ?? 0;
        $amountUsd = $get('amount_usd') ?? 0;
        $amountCop = $get('amount_cop') ?? 0;

        $usdRate = $get('../../usd_rate') ?? 0;  // Obtener la tasa USD
        $copRate = $get('../../cop_rate') ?? 0;  // Obtener la tasa COP

        $subtotalBs = $amountBs + ($amountUsd * $usdRate) + ($amountCop / $copRate);
        $set('subtotal_bs', round($subtotalBs, 2));
    }

    protected static function calculateGlobalTotals(callable $set, callable $get): void
    {
        // Obtener todos los items del repeater
        $items = $get('../../offeringItems') ?? [];

        // Inicializar los totales
        $totalBs = 0;
        $totalUsd = 0;
        $totalCop = 0;

        // Obtener las tasas de cambio desde el formulario
        $usdRate = (float) $get('../../usd_rate') ?? 1; // Tasa de cambio USD a Bs
        $copRate = (float) $get('../../cop_rate') ?? 1; // Tasa de cambio COP a Bs

        // Calcular los totales por moneda
        foreach ($items as $item) {
            $totalBs += (float) ($item['amount_bs'] ?? 0);
            $totalUsd += (float) ($item['amount_usd'] ?? 0);
            $totalCop += (float) ($item['amount_cop'] ?? 0);
        }

        // Convertir USD y COP a Bs usando las tasas de cambio
        $totalUsdToBs = $totalUsd * $usdRate;
        $totalCopToBs = $totalCop / $copRate;

        // Calcular el gran total en Bs
        $grandTotalBs = $totalBs + $totalUsdToBs + $totalCopToBs;

        // Actualizar los campos de totales globales
        $set('../../total_bs', round($totalBs, 2));
        $set('../../total_usd', round($totalUsd, 2));
        $set('../../total_cop', round($totalCop, 2));
        $set('../../total_usd_to_bs', round($totalUsdToBs, 2));
        $set('../../total_cop_to_bs', round($totalCopToBs, 2));
        $set('../../grand_total_bs', round($grandTotalBs, 2));
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
            'index' => Pages\ListOfferingTransactions::route('/'),
            'create' => Pages\CreateOfferingTransaction::route('/create'),
            'edit' => Pages\EditOfferingTransaction::route('/{record}/edit'),
        ];
    }
}