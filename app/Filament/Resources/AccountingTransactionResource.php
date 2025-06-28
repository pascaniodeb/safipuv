<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingTransactionResource\Pages;
use App\Filament\Resources\AccountingTransactionResource\RelationManagers;
use App\Models\AccountingTransaction;
use App\Models\Movement;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\VisibleToRolesTreasurer;
use App\Traits\HasAccountingAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingTransactionResource extends Resource
{
    use VisibleToRolesTreasurer, HasAccountingAccess;
    
    protected static ?string $model = AccountingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function getPluralModelLabel(): string
    {
        return 'Libro Diario';
    }

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();

        if ($user) {
            // Obtener el primer rol del usuario usando Spatie
            $roleName = $user->getRoleNames()->first();

            // Si tiene un rol, usarlo; de lo contrario, un valor predeterminado
            return $roleName ? '' . $roleName : 'Modulos';
        }

        return 'Modulos';
    }

    public static function getEloquentQuery(): Builder
    {
        \Log::info("getEloquentQuery - EJECUTÁNDOSE");
        
        $query = parent::getEloquentQuery()->accessibleRecords();
        
        \Log::info("getEloquentQuery - Query aplicada, SQL: " . $query->toSql());
        \Log::info("getEloquentQuery - Bindings: " . json_encode($query->getBindings()));
        
        return $query;
    }

    //public static function getDefaultTableQuery(): Builder
    //{
        // Usar la query con accessibleRecords, SIN filtro de mes por defecto
        //return static::getEloquentQuery();
    //}


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información General')
                ->description('Registra los detalles del movimiento contable.')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\DatePicker::make('transaction_date')
                                ->label('Fecha de la Transacción')
                                ->native(false)

                                // Formato de presentación y de guardado
                                ->displayFormat('Y-m-d')
                                ->format('Y-m-d')

                                // Rango permitido **solo al crear**:
                                // desde hace 7 meses hasta hoy
                                ->minDate(fn (string $context) => $context === 'create'
                                    ? \Carbon\Carbon::now()->subMonths(7)->startOfMonth()
                                    : null
                                )
                                ->maxDate(fn (string $context) => $context === 'create'
                                    ? \Carbon\Carbon::now()->endOfDay()
                                    : null
                                )

                                // Valor por defecto: 1.º de este mes, salvo que ya exista
                                ->default(fn (?Model $record) =>
                                    $record?->transaction_date
                                        ? $record->transaction_date
                                        : \Carbon\Carbon::now()->startOfMonth()
                                )

                                ->required(),

                            
                    
                            
                            Forms\Components\Select::make('accounting_id')
                                ->label('Contabilidad')
                                ->options(function () {
                                    $user = auth()->user(); // Obtener usuario autenticado
                            
                                    // 🔹 Si el usuario es "Supervisor Distrital", le mostramos la Contabilidad Distrital
                                    if ($user->hasRole('Supervisor Distrital')) {
                                        return \App\Models\Accounting::whereHas('treasury', function ($query) {
                                            $query->where('level', 'Distrital'); // Solo nivel Distrital
                                        })->pluck('name', 'id');
                                    }
                            
                                    // 🔹 Para los Tesoreros (Sectorial, Distrital, Regional, Nacional), usar `treasury_level`
                                    return \App\Models\Accounting::whereHas('treasury', function ($query) use ($user) {
                                        $query->where('level', $user->treasury_level); // Se mantiene la lógica actual
                                    })->pluck('name', 'id');
                                })
                                ->default(function () {
                                    $user = auth()->user();
                            
                                    // 🔹 Si el usuario es Supervisor Distrital, asignamos automáticamente la Contabilidad Distrital
                                    if ($user->hasRole('Supervisor Distrital')) {
                                        return \App\Models\Accounting::whereHas('treasury', function ($query) {
                                            $query->where('level', 'Distrital');
                                        })->value('id');
                                    }
                            
                                    // 🔹 Para los Tesoreros, usamos el `treasury_level` como en la lógica original
                                    return \App\Models\Accounting::whereHas('treasury', function ($query) use ($user) {
                                        $query->where('level', $user->treasury_level);
                                    })->value('id');
                                })
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                            
                            
                            
                            Forms\Components\Hidden::make('user_id')
                                ->default(fn () => auth()->id()),
                            
                            

                            Forms\Components\Select::make('movement_id')
                                ->label('Tipo de Movimiento')
                                ->relationship('movement', 'type')
                                ->native(false)
                                ->required()
                                ->reactive(),

                            
                        ]),
                        Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('accounting_code_id')
                                ->label('Código Contable')
                                ->options(function (callable $get) {
                                    $movementId = $get('movement_id');
                                    $user = auth()->user();
                                    $userLevel = $user->treasury_level;
                                    
                                    if (!$movementId) {
                                        return [];
                                    }
                            
                                    $codes = \App\Models\AccountingCode::where('movement_id', $movementId)
                                        ->whereHas('accounting', function ($query) use ($user, $userLevel) {
                                            $query->whereHas('treasury', function ($q) use ($user, $userLevel) {
                                                if ($user->hasRole('Supervisor Distrital')) {
                                                    $q->where('level', 'Distrital');
                                                } else {
                                                    $q->where('level', $userLevel);
                                                }
                                            });
                                        })
                                        ->get();
                            
                                    // Construimos [id => 'CODE - DESCRIPTION']
                                    return $codes->mapWithKeys(fn($code) => [
                                        $code->id => $code->code . ' - ' . $code->description,
                                    ]);
                                })
                                ->required()
                                ->native(false)
                                ->reactive()
                                ->disabled(fn (callable $get) => !$get('movement_id'))
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $description = \App\Models\AccountingCode::where('id', $state)->value('description');
                                
                                    // 📌 Mostrar descripción legible (no se guarda)
                                    $set('accounting_code_description', $description);
                                
                                    // ✅ También llenar el campo que sí se guarda en BD
                                    $set('description', $description);
                                }),
                                
                                
                            
                            
                            
                            
                            

                            // ✅ CAMPO QUE MUESTRA LA DESCRIPCIÓN DEL CÓDIGO CONTABLE
                            Forms\Components\TextInput::make('accounting_code_description')
                                ->label('Descripción del Código Contable')
                                ->disabled()
                                ->dehydrated(false) // No se guarda en la BD
                                //->columnSpan(2)
                                ->placeholder('Seleccione un código para ver su descripción'),
                        ])
                ]),

            Forms\Components\Section::make('Detalles del Movimiento')
                ->description('Especifique la divisa y otros detalles.')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('currency')
                                ->label('Divisa')
                                ->native(false)
                                ->options([
                                    'VES' => 'Bolívares (VES)',
                                    'USD' => 'Dólares (USD)',
                                    'COP' => 'Pesos Colombianos (COP)',
                                ])
                                ->default('VES')
                                ->required(),
                                
                            Forms\Components\TextInput::make('amount')
                                ->label('Monto')
                                ->numeric()
                                ->required(),

                            
                        ]),

                    Forms\Components\TextInput::make('description')
                        ->label('Descripción'),
                    
                ]),

            Forms\Components\Section::make('Evidencia del Movimiento')
                ->description('Suba una imagen del recibo o factura del movimiento contable.')
                ->schema([
                    FileUpload::make('receipt_path')
                        ->label('Subir Recibo o Factura')
                        ->image()
                        ->imagePreviewHeight('200')
                        ->downloadable() // Permite descargar la imagen
                        ->openable()     // ¡Agrega esta línea! Permite abrir la imagen en una nueva pestaña/ventana
                        ->directory('receipts')
                        //->preserveFilenames()
                        ->maxSize(2048),




                ]),

        ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('accountingCode.code')
                    ->label('Código Contable')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('movement.type')
                    ->label('Movimiento')
                    ->colors([
                        'success' => 'Ingreso',
                        'danger' => 'Egreso',
                    ])
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('currency')
                    ->label('Divisa')
                    ->colors([
                        'VES' => 'blue',
                        'USD' => 'green',
                        'COP' => 'yellow',
                    ])
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money(fn ($record) => $record->currency) // ✅ Aplica el símbolo de la moneda automáticamente
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable(),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Mes')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return '-';
                        }
                
                        // Asumiendo que $state viene como "2025-01-01"
                        // Lo parseamos con Carbon y mostramos "01/2025"
                        return \Carbon\Carbon::parse($state)->format('m/Y');
                    })
                    ->badge()
                    ->color('gray')
                    ->sortable(),
    
                Tables\Columns\BadgeColumn::make('is_closed')
                    ->label('Estado')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Cerrado' : 'Abierto')
                    ->colors([
                        'danger' => true, // Si el estado (is_closed) es true, el color será 'danger'
                        'success' => false, // Si el estado (is_closed) es false, el color será 'success'
                    ]),
                
                
                

                Tables\Columns\IconColumn::make('receipt_path')
                    ->label('Factura/Recibo')
                    ->icon(fn ($record) => $record->receipt_path ? 'heroicon-o-document' : 'heroicon-o-x-circle')
                    ->color(fn ($record) => $record->receipt_path ? 'success' : 'danger')
                    ->url(fn ($record) => $record->receipt_path ? asset('storage/' . $record->receipt_path) : null, true)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Modificación')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                // Filtro por mes mejorado
                Tables\Filters\SelectFilter::make('month_filter')
                    ->label('Mes contable')
                    ->options(function () {
                        $pdfService = app(\App\Services\AccountingPDFService::class);
                        return $pdfService->getMonthOptions();
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $month = $data['value'];
                            $startOfMonth = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                            $endOfMonth = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
                            
                            $query->whereBetween('transaction_date', [$startOfMonth, $endOfMonth]);
                        }
                    })
                    ->default(function () {
                        $pdfService = app(\App\Services\AccountingPDFService::class);
                        $months = $pdfService->getMonthOptions();
                        return array_key_first($months);
                    })
                    ->native(false),
                


                
            
                // Filtro por tipo de movimiento (Ingreso/Egreso)
                Tables\Filters\SelectFilter::make('movement_id')
                    ->label('Filtrar por Tipo de Movimiento')
                    ->native(false)
                    ->options(
                        Movement::pluck('type', 'id') // Obtiene los tipos de movimiento desde la base de datos
                    ),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                \Log::info("modifyQueryUsing - EJECUTÁNDOSE");
                
                // Aplicar el scope aquí también como backup
                $user = auth()->user();
                if ($user) {
                    // Crear instancia temporal para usar el trait
                    $tempInstance = new class {
                        use \App\Traits\HasAccountingAccess;
                    };
                    
                    $accounting = $tempInstance->getUserAccounting();
                    if ($accounting) {
                        $query->where('accounting_id', $accounting->id);
                        
                        if ($user->hasAnyRole(['Tesorero Sectorial', 'Contralor Sectorial']) && $user->sector_id) {
                            $query->where('sector_id', $user->sector_id);
                            \Log::info("modifyQueryUsing - Aplicando filtro sectorial: " . $user->sector_id);
                        } elseif ($user->hasRole('Supervisor Distrital') && $user->district_id) {
                            $query->where('district_id', $user->district_id);
                            \Log::info("modifyQueryUsing - Aplicando filtro distrital: " . $user->district_id);
                        } elseif ($user->hasAnyRole(['Tesorero Regional', 'Contralor Regional']) && $user->region_id) {
                            $query->where('region_id', $user->region_id);
                            \Log::info("modifyQueryUsing - Aplicando filtro regional: " . $user->region_id);
                        }
                    }
                }
                
                \Log::info("modifyQueryUsing - Query final: " . $query->toSql());
                return $query;
            })
            
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => ! $record->is_closed),
                Tables\Actions\ViewAction::make(),

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

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\AccountingTransactionResource\Widgets\AccountingTransactionStats::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountingTransactions::route('/'),
            'create' => Pages\CreateAccountingTransaction::route('/create'),
            'edit' => Pages\EditAccountingTransaction::route('/{record}/edit'),
        ];
    }
}