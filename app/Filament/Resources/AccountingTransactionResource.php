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
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingTransactionResource extends Resource
{
    use VisibleToRolesTreasurer;
    
    protected static ?string $model = AccountingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function getPluralModelLabel(): string
    {
        return 'Contabilidad';
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
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return match (true) {
            // ✅ Tesorero Sectorial solo ve registros de su sector
            $user->hasRole(['Tesorero Sectorial', 'Contralor Sectorial']) => $query->where('sector_id', $user->sector_id),

            // ✅ Supervisor Distrital solo ve los registros que ÉL mismo ha hecho
            $user->hasRole('Supervisor Distrital') => $query->where('user_id', $user->id),

            // ✅ Tesorero Regional solo ve registros de su región
            $user->hasRole(['Tesorero Regional', 'Contralor Regional']) => $query->where('region_id', $user->id),

            // ✅ Tesorero Nacional ve todos los registros
            $user->hasRole(['Tesorero Nacional', 'Contralor Nacional']) => $query->where('user_id', $user->id),

            // ❌ Otros usuarios no ven nada
            default => $query->whereNull('id'),
        };
    }

    public static function getDefaultTableQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('month', now()->format('Y-m'));
    }


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
                                ->native(false) // Usar el calendario interactivo
                                ->format('Y-m-d')
                                ->minDate(now()->startOfMonth()) // ❌ No permite seleccionar fechas anteriores al inicio del mes
                                ->maxDate(now()->endOfMonth()) // ❌ No permite seleccionar fechas futuras fuera del mes
                                ->default(now()) // ✅ Establece la fecha por defecto como hoy
                                ->required(),
                            
                            Forms\Components\Hidden::make('month')
                                ->default(fn (callable $get) => date('Y-m', strtotime($get('transaction_date')))),
                            

                            
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

                            Forms\Components\Select::make('accounting_code_id')
                                ->label('Código Contable')
                                ->options(function (callable $get) {
                                    $movementId = $get('movement_id');
                                    $user = auth()->user(); // Obtener usuario autenticado
                                    $userLevel = $user->treasury_level; // 🔹 Nivel de contabilidad para Tesoreros
                            
                                    return $movementId
                                        ? \App\Models\AccountingCode::where('movement_id', $movementId)
                                            ->whereHas('accounting', function ($query) use ($user, $userLevel) {
                                                $query->whereHas('treasury', function ($q) use ($user, $userLevel) {
                                                    if ($user->hasRole('Supervisor Distrital')) {
                                                        $q->where('level', 'Distrital'); // 🔹 Excepción para el Supervisor Distrital
                                                    } else {
                                                        $q->where('level', $userLevel); // 🔹 Mantiene la lógica actual para Tesoreros
                                                    }
                                                });
                                            })
                                            ->pluck('code', 'id')
                                        : [];
                                })
                                ->required()
                                ->native(false)
                                ->reactive()
                                ->disabled(fn (callable $get) => !$get('movement_id'))
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $description = \App\Models\AccountingCode::where('id', $state)->value('description');
                                    $set('accounting_code_description', $description);
                                }),
                            
                            
                            
                            

                            // ✅ CAMPO QUE MUESTRA LA DESCRIPCIÓN DEL CÓDIGO CONTABLE
                            Forms\Components\TextInput::make('accounting_code_description')
                                ->label('Descripción del Código Contable')
                                ->disabled()
                                ->dehydrated(false) // No se guarda en la BD
                                ->columnSpan(2)
                                ->placeholder('Seleccione un código para ver su descripción'),
                        ]),
                ]),

            Forms\Components\Section::make('Detalles del Movimiento')
                ->description('Especifique la divisa y otros detalles.')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('currency')
                                ->label('Divisa')
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

                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->rows(2)
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Evidencia del Movimiento')
                ->description('Suba una imagen del recibo o factura del movimiento contable.')
                ->schema([
                    Forms\Components\FileUpload::make('receipt_path')
                        ->label('Subir Recibo o Factura')
                        ->image()
                        ->directory('receipts') // Carpeta donde se guardarán los archivos
                        ->preserveFilenames()
                        ->maxSize(2048), // 2MB máximo
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

                Tables\Columns\TextColumn::make('month')
                    ->label('Mes')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('month')
                    ->label('Estado del Mes')
                    ->colors([
                        'yellow' => fn ($record) => $record->month < now()->format('Y-m'),
                        'green' => fn ($record) => $record->month == now()->format('Y-m'),
                    ])
                    ->formatStateUsing(fn ($record) => 
                        $record->month < now()->format('Y-m') ? 'Cerrado' : 'Abierto'
                    ),
                

                Tables\Columns\IconColumn::make('receipt_path')
                    ->label('Factura/Recibo')
                    ->icon(fn ($record) => $record->receipt_path ? 'heroicon-o-document' : 'heroicon-o-x-circle')
                    ->color(fn ($record) => $record->receipt_path ? 'success' : 'danger')
                    ->url(fn ($record) => $record->receipt_path ? asset('storage/' . $record->receipt_path) : null, true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Modificación')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                // Filtro por mes
                Tables\Filters\SelectFilter::make('month')
                    ->label('Filtrar por Mes')
                    ->options(
                        AccountingTransaction::select('month')
                            ->distinct()
                            ->orderByDesc('month')
                            ->pluck('month', 'month')
                    ),
            
                // Filtro por tipo de movimiento (Ingreso/Egreso)
                Tables\Filters\SelectFilter::make('movement_id')
                    ->label('Filtrar por Tipo de Movimiento')
                    ->options(
                        Movement::pluck('type', 'id') // Obtiene los tipos de movimiento desde la base de datos
                    ),
            ])
            
            ->actions([
                Tables\Actions\EditAction::make()
                    ->disabled(fn ($record) => $record->month <= now()->subMonth()->format('Y-m')),

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
            'index' => Pages\ListAccountingTransactions::route('/'),
            'create' => Pages\CreateAccountingTransaction::route('/create'),
            'edit' => Pages\EditAccountingTransaction::route('/{record}/edit'),
        ];
    }
}