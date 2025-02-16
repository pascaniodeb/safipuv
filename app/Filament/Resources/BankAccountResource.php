<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Filament\Resources\BankAccountResource\RelationManagers;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\TreasurersScopedAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankAccountResource extends Resource
{
    use TreasurersScopedAccess;
    
    protected static ?string $model = BankAccount::class;

    protected static ?int $navigationSort = 6; // Orden

    protected static ?string $navigationIcon = 'heroicon-s-banknotes';

    public static function getPluralModelLabel(): string
    {
        return 'Cuenta Bancaria';
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

        // Si el usuario no es administrador ni tesorero nacional, filtrar por user_id
        if (!auth()->user()->hasRole(['Administrador', 'Tesorero Nacional'])) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección: Información Básica
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        // Rol del usuario
                        Forms\Components\TextInput::make('role_name')
                            ->label('Rol')
                            ->disabled() // No editable
                            ->required()
                            ->default(fn () => auth()->user()->getRoleNames()->first()) // Rol del usuario autenticado en creación
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record && $record->user) {
                                    $roleName = $record->user->getRoleNames()->first(); // Obtén el rol del usuario asociado al registro
                                    if ($roleName) {
                                        $set('role_name', $roleName); // Configura el campo con el nombre del rol
                                    }
                                } else {
                                    // Si no hay registro, usa el rol del usuario autenticado
                                    $set('role_name', auth()->user()->getRoleNames()->first());
                                }
                            }),
                            Forms\Components\Hidden::make('hidden_role_name')
                                ->default(fn ($get) => $get('role_name')), // Toma el valor del campo deshabilitado
                                
                        // Tipo de Cuenta
                        Forms\Components\Select::make('bank_account_type_id')
                            ->label('Tipo de Cuenta')
                            ->relationship('accountType', 'name')
                            ->required()
                            ->reactive()
                            ->default(fn () => \App\Models\BankAccountType::first()?->id)
                            ->afterStateUpdated(fn ($state, callable $set) => self::handleAccountTypeSelection($state, $set)),

                        // Usuario (Solo para Personal)
                        Forms\Components\Select::make('user_id')
                            ->label('Cédula')
                            ->disabled() // Deshabilitado visualmente
                            ->relationship('user', 'username')
                            ->required()
                            ->default(fn () => auth()->user()->id) // Usuario autenticado en creación
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record && $record->user) {
                                    $set('user_id', $record->user->id); // Configura el usuario relacionado
                                } else {
                                    // Si no hay un registro, usa el usuario autenticado
                                    $set('user_id', auth()->user()->id);
                                }
                            }),
                            Forms\Components\Hidden::make('hidden_user_id')
                                ->default(fn ($get) => $get('user_id')) // Toma el valor del campo deshabilitado
                                ->required(), // Asegura que siempre esté presente

                        // Email (Solo para Personal)
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->disabled()
                            ->required()
                            ->visible(fn ($get) => $get('bank_account_type_id') == 1)
                            ->default(fn () => auth()->user()->email)
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record && $record->user) {
                                    $set('email', $record->user->email);
                                } else {
                                    $set('email', auth()->user()->email);
                                }
                            }),
                            Forms\Components\Hidden::make('hidden_email')
                                ->default(fn ($get) => $get('email')), // Toma el valor del campo deshabilitado
                        
                        // RIF (Solo para Jurídica)
                        Forms\Components\TextInput::make('tax_id')
                            ->label('RIF')
                            ->required()
                            ->placeholder('Ejemplo: J-12345678-9')
                            ->visible(fn ($get) => $get('bank_account_type_id') == 2),

                        // Razón Social (Solo para Jurídica)
                        Forms\Components\TextInput::make('business_name')
                            ->label('Razón Social')
                            ->required()
                            ->visible(fn ($get) => $get('bank_account_type_id') == 2),
                        ])
                        ->columns([
                            'sm' => 1,
                            'lg' => 2,
                        ]),
                    

                // Sección: Ubicación Geográfica
                Forms\Components\Section::make('Ubicación Geográfica')
                    ->schema([
                        // Region
                        Forms\Components\Select::make('region_id')
                            ->label('Región')
                            ->disabled() // Deshabilitado visualmente
                            ->relationship('region', 'name') // Relación con el modelo de regiones
                            ->required()
                            ->default(fn () => auth()->user()->region_id) // Toma la región del usuario autenticado al crear
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record) {
                                    $set('region_id', $record->region_id); // Configura la región según el registro actual
                                } else {
                                    $set('region_id', auth()->user()->region_id); // Configura la región del usuario autenticado
                                }
                            }),
                        Forms\Components\Hidden::make('hidden_region_id')
                            ->default(fn ($get) => $get('region_id')), // Sincroniza el valor del campo deshabilitado

                        // District
                        Forms\Components\Select::make('district_id')
                            ->label('Distrito')
                            ->disabled() // Deshabilitado visualmente
                            ->relationship('district', 'name') // Relación con el modelo de distritos
                            ->required()
                            ->default(fn () => auth()->user()->district_id) // Toma el district del usuario autenticado al crear
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record) {
                                    $set('district_id', $record->district_id); // Configura el district según el registro actual
                                } else {
                                    $set('district_id', auth()->user()->district_id); // Configura el district del usuario autenticado
                                }
                            }),
                        Forms\Components\Hidden::make('hidden_district_id')
                            ->default(fn ($get) => $get('district_id')), // Sincroniza el valor del campo deshabilitado
                       
                        // Sector
                        Forms\Components\Select::make('sector_id')
                            ->label('Sector')
                            ->disabled() // Deshabilitado visualmente
                            ->relationship('sector', 'name') // Relación con el modelo de sectors
                            ->required()
                            ->default(fn () => auth()->user()->sector_id) // Toma el sector del usuario autenticado al crear
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record) {
                                    $set('sector_id', $record->sector_id); // Configura el sector según el registro actual
                                } else {
                                    $set('sector_id', auth()->user()->sector_id); // Configura el sector del usuario autenticado
                                }
                            }),
                        Forms\Components\Hidden::make('hidden_sector_id')
                            ->default(fn ($get) => $get('sector_id')), // Sincroniza el valor del campo deshabilitado
                        
                    ])
                    ->columns([
                        'sm' => 1,
                        'lg' => 3,
                    ]),

                // Sección: Detalles de la Cuenta
                Forms\Components\Section::make('Detalles de la Cuenta')
                    ->schema([
                        Forms\Components\Select::make('bank_id')
                            ->label('Seleccione un Banco')
                            ->placeholder('Seleccione...')
                            ->relationship('bank', 'name')
                            ->required(),
                        
                        Forms\Components\Select::make('bank_transaction_id')
                            ->label('Tipo de Transacción')
                            ->placeholder('Seleccione...')
                            ->relationship('transaction', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => self::handleTransactionSelection($state, $set)),

                        Forms\Components\TextInput::make('account_number')
                            ->label('Número de Cuenta')
                            ->required()
                            ->placeholder('Ejemplo: 1234567890123456')
                            ->visible(fn ($get) => $get('bank_transaction_id') == 1),

                        Forms\Components\TextInput::make('mobile_payment_number')
                            ->label('Número de Pago Móvil')
                            ->nullable()
                            ->rules(['numeric', 'digits:11'])
                            ->placeholder('Ejemplo: 04121234569')
                            ->visible(fn ($get) => $get('bank_transaction_id') == 2),

                        Forms\Components\Toggle::make('active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns([
                        'sm' => 1,
                        'lg' => 3,
                    ]),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Banco
                Tables\Columns\TextColumn::make('bank.name')
                    ->label('Banco')
                    ->sortable()
                    ->searchable(),
            
                // Tipo de Transacción
                Tables\Columns\TextColumn::make('transaction.name')
                    ->label('Transacción')
                    ->sortable()
                    ->searchable(),
            
                // Tipo de Cuenta
                Tables\Columns\TextColumn::make('accountType.name')
                    ->label('Cuenta')
                    ->sortable()
                    ->searchable(),
            
                // Usuario
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Cédula')
                    ->searchable(),
            
                // Número de Cuenta
                Tables\Columns\TextColumn::make('account_number')
                    ->label('Número de Cuenta')
                    ->searchable(),
            
                // Número de Pago Móvil
                Tables\Columns\TextColumn::make('mobile_payment_number')
                    ->label('Número de Pago Móvil')
                    ->searchable(),
            
                // Fecha de Creación
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            
                // Fecha de Actualización
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
        
            ->filters([
                // Filtro por Banco
                Tables\Filters\SelectFilter::make('bank_id')
                    ->label('Banco')
                    ->relationship('bank', 'name'),
            
                // Filtro por Tipo de Transacción
                Tables\Filters\SelectFilter::make('bank_transaction_id')
                    ->label('Tipo de Transacción')
                    ->relationship('transaction', 'name'),
            
                // Filtro por Tipo de Cuenta
                Tables\Filters\SelectFilter::make('bank_account_type_id')
                    ->label('Cuenta')
                    ->relationship('accountType', 'name'),
            
                // Filtro por Estado Activo
                Tables\Filters\Filter::make('active')
                    ->label('Solo Activas')
                    ->query(fn (Builder $query) => $query->where('active', true)),
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


    // Aquí se coloca el método
    protected static function loadUserRegionsAndEmail($userId, callable $set, $record = null)
    {
        $user = $record?->user ?? \App\Models\User::find($userId);

        if ($user) {
            // Carga automáticamente regiones, distritos y sectores desde el usuario
            $set('region_id', $user->region_id);
            $set('district_id', $user->district_id);
            $set('sector_id', $user->sector_id);

            // Carga el correo electrónico
            $set('email', $user->email);
        } else {
            // Limpia los campos si no hay usuario seleccionado
            $set('region_id', null);
            $set('district_id', null);
            $set('sector_id', null);
            $set('email', null);
        }
    }


    protected static function handleAccountTypeSelection($state, callable $set)
    {
        if ($state == 1) { // ID del tipo de cuenta "Personal"
            $user = auth()->user();

            if ($user) {
                $set('user_id', $user->id); // Asigna automáticamente el usuario autenticado
                $set('email', $user->email); // Asigna automáticamente el email del usuario autenticado
            }
        } else {
            $set('user_id', null); // Resetea el campo si no es Personal
            $set('email', null);   // Resetea el campo si no es Personal
        }
    }

    protected static function handleTransactionSelection($state, callable $set)
    {
        if ($state == 1) { // Transferencia
            $set('account_number', null); // Resetea Número de Cuenta si es Transferencia
            $set('mobile_payment_number', null); // Resetea Pago Móvil
        } elseif ($state == 2) { // Pago Móvil
            $set('account_number', null); // Resetea Número de Cuenta
        }
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
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}