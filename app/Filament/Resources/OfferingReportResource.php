<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferingReportResource\Pages;
use App\Filament\Resources\OfferingReportResource\RelationManagers;
use App\Models\Offering;
use App\Models\OfferingReport;
use App\Models\OfferingItem;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\EditAction;
use App\Models\Pastor;
use App\Models\Church;
use App\Models\Treasury;
use App\Models\BankTransaction; // Modelo para la tabla bank_transactions
use App\Models\Bank; // Modelo para la tabla banks
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use App\Traits\OfferingReportFilterTrait;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferingReportResource extends Resource
{
    use OfferingReportFilterTrait;
    
    protected static ?string $model = OfferingReport::class;

    protected static ?string $navigationIcon = 'heroicon-s-pencil-square';

    public static function getPluralModelLabel(): string
    {
        return 'Registrar Ofrendas';
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

    //public static function getEloquentQuery(): Builder
    //{
        //return static::getFilteredQuery(); // 📌 Aplica el filtrado optimizado
    //}

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('Tesorero Sectorial'); // 📌 Solo este rol puede crear registros
    }

    public static function canView($record): bool
    {
        return true; // 📌 Todos los roles pueden ver los registros
    }
    
    public static function form(Form $form): Form
    {
        
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        // Datos del Pastor
                        Forms\Components\Section::make('Datos del Pastor e Iglesia')
                            ->schema(static::getDetailsFormSchema())
                            ->columns(2),

                        // Ofrendas
                        Forms\Components\Section::make('Ofrendas')
                            ->headerActions([
                                Action::make('reset')
                                    ->modalHeading('¿Estás seguro?')
                                    ->modalDescription('Esto eliminará todos los productos de la orden.')
                                    ->requiresConfirmation()
                                    ->color('danger')
                                    ->action(fn (Forms\Set $set) => $set('offering_items', [])),
                            ])
                            ->schema([
                                static::getItemsRepeater(),
                            ]),

                        // Totales Globales
                        Forms\Components\Section::make('Totales Globales')
                            ->schema([
                                // 📌 Estado de la Orden
                                // 📌 Estado de la Orden
                                Forms\Components\ToggleButtons::make('status')
                                    ->inline()
                                    ->options([
                                        'pendiente' => 'Pendiente',
                                        'aprobado' => 'Aprobado',
                                    ])
                                    ->colors([
                                        'pendiente' => 'warning', // Amarillo para "Pendiente"
                                        'aprobado' => 'success',  // Verde para "Aprobado"
                                    ])
                                    ->required()
                                    ->dehydrated() // ✅ Asegura que el valor se envía aunque el campo esté deshabilitado
                                    ->disabled(function () {
                                        return !Auth::user()->hasAnyRole([
                                            'Tesorero Sectorial', 
                                        ]);
                                    }),

                                    
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('total_bs')
                                            ->label('Total de Bolívares')
                                            ->numeric()
                                            ->reactive()
                                            ->live()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('total_usd')
                                            ->label('Total de Dólares')
                                            ->numeric()
                                            ->reactive()
                                            ->live()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated(),
                                        
                                        Forms\Components\TextInput::make('total_cop')
                                            ->label('Total de Pesos')
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
                    ])
                    ->columnSpan(['lg' => fn (?OfferingReport $record) => $record === null ? 3 : 3]),
            ])
            ->columns(3)
            ->statePath('data'); // Asegúrate de que los datos se guarden correctamente
            
    }

    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Mes y año del reporte
                TextColumn::make('month')
                    ->label('Mes')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::createFromFormat('Y-m', $state)->translatedFormat('F Y'))
                    ->sortable(),

                // Número de orden
                TextColumn::make('number_report')
                    ->label('Número de Reporte')
                    ->searchable()
                    ->sortable(),

                // Pastor asociado
                TextColumn::make('pastor.name')
                    ->label('Nombre del Pastor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pastor.lastname')
                    ->label('Apellido del Pastor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pastor.number_cedula')
                    ->label('Cédula del Pastor')
                    ->searchable(),
                
                    
                TextColumn::make('status')
                    ->badge()
                    ->label('Estado')
                    ->sortable()
                    ->colors([
                        'warning' => 'pendiente', // Amarillo para "pendiente"
                        'success' => 'aprobado',  // Verde para "aprobado"
                    ]),
                    
                                  

                // Iglesia asociada
                TextColumn::make('church.name')
                    ->label('Iglesia')
                    ->searchable()
                    ->sortable(),

                // Región, distrito y sector
                TextColumn::make('region.name')
                    ->label('Región')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('district.name')
                    ->label('Distrito')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sector.name')
                    ->label('Sector')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Totales globales
                TextColumn::make('total_bs')
                    ->label('Total Bs')
                    ->money('VES') // Formatear como moneda
                    ->sortable(),

                TextColumn::make('total_usd')
                    ->label('Total USD')
                    ->money('USD') // Formatear como moneda
                    ->sortable(),

                TextColumn::make('total_cop')
                    ->label('Total COP')
                    ->money('COP') // Formatear como moneda
                    ->sortable(),

                TextColumn::make('total_usd_to_bs')
                    ->label('Total USD a Bs')
                    ->money('VES') // Formatear como moneda
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_cop_to_bs')
                    ->label('Total COP a Bs')
                    ->money('VES') // Formatear como moneda
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('grand_total_bs')
                    ->label('Gran Total Bs')
                    ->money('VES') // Formatear como moneda
                    ->sortable(),

                // Fecha de creación
                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Fecha de actualización
                TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por mes
                SelectFilter::make('month')
                    ->label('Filtrar por Mes')
                    ->options(
                        OfferingReport::select('month')
                            ->distinct()
                            ->orderByDesc('month')
                            ->pluck('month', 'month')
                    )
                    ->searchable(),
            
                // Filtro por estado (pendiente/aprobado)
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'aprobado' => 'Aprobado',
                    ])
                    ->searchable(),
            
                // Filtro por pastor
                SelectFilter::make('pastor_id')
                    ->label('Pastor')
                    ->relationship('pastor', 'name')
                    ->searchable(),
            
                // Filtro por iglesia
                SelectFilter::make('church_id')
                    ->label('Iglesia')
                    ->relationship('church', 'name')
                    ->searchable(),
            
                // Filtro por región
                SelectFilter::make('region_id')
                    ->label('Región')
                    ->relationship('region', 'name')
                    ->searchable(),
            
                // Filtro por distrito
                SelectFilter::make('district_id')
                    ->label('Distrito')
                    ->relationship('district', 'name')
                    ->searchable(),
            
                // Filtro por sector
                SelectFilter::make('sector_id')
                    ->label('Sector')
                    ->relationship('sector', 'name')
                    ->searchable(),
            
                // Filtro por total en Bs
                Filter::make('total_bs')
                    ->form([
                        TextInput::make('total_bs_min')
                            ->numeric()
                            ->label('Mínimo'),
                        TextInput::make('total_bs_max')
                            ->numeric()
                            ->label('Máximo'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['total_bs_min'], fn ($q) => $q->where('total_bs', '>=', $data['total_bs_min']))
                            ->when($data['total_bs_max'], fn ($q) => $q->where('total_bs', '<=', $data['total_bs_max']));
                    }),
            
                // Filtro por total en USD
                Filter::make('total_usd')
                    ->form([
                        TextInput::make('total_usd_min')
                            ->numeric()
                            ->label('Mínimo'),
                        TextInput::make('total_usd_max')
                            ->numeric()
                            ->label('Máximo'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['total_usd_min'], fn ($q) => $q->where('total_usd', '>=', $data['total_usd_min']))
                            ->when($data['total_usd_max'], fn ($q) => $q->where('total_usd', '<=', $data['total_usd_max']));
                    }),
            
                // Filtro por total en COP
                Filter::make('total_cop')
                    ->form([
                        TextInput::make('total_cop_min')
                            ->numeric()
                            ->label('Mínimo'),
                        TextInput::make('total_cop_max')
                            ->numeric()
                            ->label('Máximo'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['total_cop_min'], fn ($q) => $q->where('total_cop', '>=', $data['total_cop_min']))
                            ->when($data['total_cop_max'], fn ($q) => $q->where('total_cop', '<=', $data['total_cop_max']));
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfferingReports::route('/'),
            'create' => Pages\CreateOfferingReport::route('/create'),
            'edit' => Pages\EditOfferingReport::route('/{record}/edit'),
        ];
    }

    /** @return Forms\Components\Component[] */
    public static function getDetailsFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\DatePicker::make('created_at')
                        ->label('Fecha de Creación')
                        ->default(now())
                        ->dehydrated()
                        ->disabled(),
                    
                    TextInput::make('number_report')
                        ->label('Número de Reporte')
                        ->default(fn () => OfferingReport::generateReportNumber()) // Muestra el número al usuario
                        ->disabled()
                        ->dehydrated()
                        ->unique(OfferingReport::class, 'number_report', ignoreRecord: true)
                        ->required(),
                    
                    
                    
                    
                    Forms\Components\Select::make('month')
                        ->label('Mes de Registro')
                        ->options(function () {
                            $today = \Carbon\Carbon::now();
                            $months = [];
                    
                            // Generar los últimos 2 meses + el mes actual
                            for ($i = 0; $i <= 2; $i++) {
                                $month = $today->copy()->subMonthsNoOverflow($i);
                                $months[$month->format('Y-m')] = $month->translatedFormat('F Y'); // Formato legible (nombre del mes y año)
                            }
                    
                            return array_reverse($months); // Ordenar de más antiguo a más reciente
                        })
                        ->placeholder('Seleccione un mes') // Placeholder para guiar al usuario
                        ->required() // Hacer que el campo sea obligatorio
                        ->reactive() // Reactivo para cambios dinámicos
                        ->disabled(function () {
                            // Deshabilitar el campo si el usuario no tiene los roles permitidos
                            return !Auth::user()->hasAnyRole([
                                'Tesorero Sectorial', 
                            ]);
                        }),

                        Forms\Components\Select::make('treasury_id')
                        ->label('Tesorería')
                        ->options(fn () => \App\Models\Treasury::where('level', auth()->user()->treasury_level)->pluck('name', 'id'))
                        ->default(fn () => \App\Models\Treasury::where('level', auth()->user()->treasury_level)->value('id'))
                        ->disabled()
                        ->required(),

                    // Campo Select para elegir el pastor
                    Forms\Components\Select::make('pastor_id')
                        ->label('Seleccione un Pastor')
                        ->options(function () {
                            $user = auth()->user();
                            if (method_exists($user, 'hasRole')) {
                                return match (true) {
                                    $user->hasRole('Tesorero Nacional') => Pastor::selectRaw("id, CONCAT(name, ' ', lastname) as full_name")
                                        ->orderBy('name')
                                        ->pluck('full_name', 'id'),
                                    $user->hasRole('Tesorero Regional') => \App\Models\Pastor::where('region_id', $user->region_id)
                                        ->selectRaw("id, CONCAT(name, ' ', lastname) as full_name")
                                        ->orderBy('name')
                                        ->pluck('full_name', 'id'),
                                    $user->hasRole('Supervisor Distrital') => \App\Models\Pastor::where('district_id', $user->district_id)
                                        ->selectRaw("id, CONCAT(name, ' ', lastname) as full_name")
                                        ->orderBy('name')
                                        ->pluck('full_name', 'id'),
                                    $user->hasRole('Tesorero Sectorial') => \App\Models\Pastor::where('sector_id', $user->sector_id)
                                        ->selectRaw("id, CONCAT(name, ' ', lastname) as full_name")
                                        ->orderBy('name')
                                        ->pluck('full_name', 'id'),
                                    default => [],
                                };
                            }
                            return [];
                        })
                        ->searchable()
                        ->required()
                        ->reactive()
                        //->columnSpan(2)
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            // Buscar el pastor seleccionado con las relaciones `ministry` y `type` cargadas
                            $pastor = Pastor::with(['ministry', 'type'])->find($state);

                            if ($pastor) {
                                // Mostrar información en los campos deshabilitados (solo visual, no se guardan)
                                $set('pastor_number_cedula', $pastor->number_cedula);
                                $set('pastor_code', $pastor->ministry?->code_pastor);
                                $set('pastor_type_name', $pastor->type?->name);
                                $set('pastor_phone_mobile', $pastor->phone_mobile);
                                $set('phone_house', $pastor->phone_house);
                                $set('email', $pastor->email);
                            
                                // Enviar solo pastor_type_id al backend
                                $set('pastor_type_id', $pastor->type?->id);
                            } else {
                                // Limpiar los valores si el pastor no se encuentra
                                $set('pastor_number_cedula', null);
                                $set('pastor_code', null);
                                $set('pastor_type_name', null);
                                $set('pastor_phone_mobile', null);
                                $set('phone_house', null);
                                $set('email', null);
                                $set('pastor_type_id', null);
                            }
                        })
                        ->disabled(function () {
                            // Deshabilitar el campo si el usuario no tiene los roles permitidos
                            return !Auth::user()->hasAnyRole([
                                'Tesorero Sectorial', 
                            ]);
                        }),

                ])
                ->columnSpan(3),

                

                
                Forms\Components\Grid::make(4)
                    ->schema([
                        

                        // Otros campos del formulario
                        TextInput::make('pastor_number_cedula')
                            ->label('Número de Cédula')
                            ->disabled(),
                            //->dehydrated(),
                        TextInput::make('pastor_code')
                            ->label('Código Pastoral')
                            ->disabled(),
                            //->dehydrated(),
                        TextInput::make('pastor_type_name')
                            ->label('Tipo de Pastor')
                            ->disabled(),
                        TextInput::make('pastor_phone_mobile')
                            ->label('Teléfono Móvil')
                            ->disabled(),
                            //->dehydrated(),
                        TextInput::make('phone_house')
                            ->label('Teléfono Fijo')
                            ->disabled(),
                            //>dehydrated(),
                        TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->disabled(),
                            //->dehydrated(),
                        // **Campo oculto que sí se guarda en la base de datos**
                        Forms\Components\Hidden::make('pastor_type_id')->dehydrated(),
                    ])
                    ->columns(4),

            

            // 📌 Notas adicionales
            Forms\Components\Textarea::make('remarks')
                ->label('Observaciones')
                ->placeholder('Añadir notas o comentarios')
                ->maxLength(250)
                ->rows(2)
                ->columnSpan('full') // Ocupará el ancho completo
                ->disabled(function () {
                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                    return !Auth::user()->hasAnyRole([
                        'Tesorero Sectorial', 
                    ]);
                }),
            
            
                    Forms\Components\Grid::make(3)
                        ->schema([
                            // Campo Select para elegir la iglesia
                            Forms\Components\Select::make('church_id')
                                ->label('Seleccione una Iglesia')
                                ->options(function () {
                                    $user = auth()->user();
                                    if (method_exists($user, 'hasRole')) {
                                        return match (true) {
                                            $user->hasRole('Tesorero Nacional') => Church::orderBy('name') // Ordenar por nombre de forma ascendente
                                                ->pluck('name', 'id'),
                                            $user->hasRole('Tesorero Regional') => Church::where('region_id', $user->region_id)
                                                ->orderBy('name') // Ordenar por nombre de forma ascendente
                                                ->pluck('name', 'id'),
                                            $user->hasRole('Supervisor Distrital') => Church::where('district_id', $user->district_id)
                                                ->orderBy('name') // Ordenar por nombre de forma ascendente
                                                ->pluck('name', 'id'),
                                            $user->hasRole('Tesorero Sectorial') => Church::where('sector_id', $user->sector_id)
                                                ->orderBy('name') // Ordenar por nombre de forma ascendente
                                                ->pluck('name', 'id'),
                                            default => [],
                                        };
                                    }
                                    return [];
                                })
                                ->searchable()
                                ->required()
                                //->columnSpan(2)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Buscar la iglesia seleccionada con las relaciones cargadas
                                    $church = Church::with(['region', 'district', 'sector'])->find($state);

                                    if ($church) {
                                        // Rellenar automáticamente los campos relacionados con la iglesia
                                        $set('code_church', $church->code_church);
                                        $set('church_address', $church->address);
                                        // Mostrar nombres de Región, Distrito y Sector
                                        $set('region_name', $church->region?->name);
                                        $set('district_name', $church->district?->name);
                                        $set('sector_name', $church->sector?->name);

                                        // Guardar valores reales en los campos ocultos
                                        $set('region_id', $church->region?->id);
                                        $set('district_id', $church->district?->id);
                                        $set('sector_id', $church->sector?->id);
                                    } else {
                                        // Limpiar los campos si no se encuentra la iglesia
                                        $set('code_church', null);
                                        $set('church_address', null);
                                        $set('region_name', null);
                                        $set('district_name', null);
                                        $set('sector_name', null);
                                        $set('region_id', null);
                                        $set('district_id', null);
                                        $set('sector_id', null);
                                    }
                                })
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Tesorero Sectorial', 
                                    ]);
                                }),

                            // Otros campos del formulario
                            Forms\Components\TextInput::make('code_church')
                                ->label('Código de Iglesia')
                                ->disabled(),
                                //->dehydrated(),
                            Forms\Components\TextInput::make('church_address')
                                ->label('Dirección de Iglesia')
                                ->columnSpan('full')
                                ->disabled(),
                                //->dehydrated(),
                            Forms\Components\TextInput::make('region_name')
                                ->label('Región')
                                ->disabled(),
                            Forms\Components\TextInput::make('district_name')
                                ->label('Distrito')
                                ->disabled(),
                            Forms\Components\TextInput::make('sector_name')
                                ->label('Sector')
                                ->disabled(),
                            // **Campos ocultos para enviar valores al backend**
                            Forms\Components\Hidden::make('region_id')->dehydrated(),
                            Forms\Components\Hidden::make('district_id')->dehydrated(),
                            Forms\Components\Hidden::make('sector_id')->dehydrated(),
                            
                        ])
                        ->columns(3),
                
                            
                
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

                
                
        ];
    }

    

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('offering_items')
            ->relationship('offeringItems')
            ->label('Items')
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('offering_category_id')
                            ->label('Tipo de Ofrenda')
                            ->options(function (callable $get) {
                                $pastorId = $get('pastor_id');
                                $month = $get('month');

                                // Obtener todas las categorías disponibles
                                $categories = \App\Models\OfferingCategory::pluck('name', 'id');

                                if ($pastorId && $month) {
                                    // Obtener las categorías ya registradas para este pastor y mes
                                    $usedCategories = \App\Models\OfferingItem::whereHas('offeringReport', function ($query) use ($pastorId, $month) {
                                        $query->where('pastor_id', $pastorId)
                                            ->where('month', $month);
                                    })->pluck('offering_category_id')->toArray();

                                    // Filtrar las categorías disponibles
                                    $categories = $categories->filter(function ($name, $id) use ($usedCategories) {
                                        return !in_array($id, $usedCategories);
                                    });
                                }

                                return $categories;
                            })
                            ->required()
                            ->reactive()
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
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
                            })
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),

                        Hidden::make('show_fields') // Campo oculto para controlar la visibilidad
                            ->default(false),

                        Select::make('bank_id')
                            ->label('Banco')
                            ->placeholder('Seleccione un banco...')
                            ->options(Bank::pluck('name', 'id'))
                            ->visible(fn (callable $get) => $get('show_fields'))
                            ->nullable()
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),

                        DatePicker::make('transaction_date')
                            ->label('Fecha de la Transacción')
                            ->visible(fn (callable $get) => $get('show_fields'))
                            ->nullable()
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),

                        TextInput::make('transaction_number')
                            ->label('Número de Transacción')
                            ->visible(fn (callable $get) => $get('show_fields'))
                            ->nullable()
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),
                    ]),

                Grid::make(4)
                    ->schema([
                        TextInput::make('amount_bs')
                            ->label('Monto en Bs')
                            ->numeric()
                            ->default(0.00)
                            ->live(debounce: 500) // 🔹 Evita que borre el número al escribir rápido
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $formattedState = (float) str_replace(',', '.', $state); // 🔹 Convierte ',' en '.' y fuerza a float
                                $set('amount_bs', number_format($formattedState, 2, '.', '')); // 🔹 Formato correcto con 2 decimales
                                
                                self::calculateSubtotal($set, $get);
                                self::calculateGlobalTotals($set, $get);
                            })
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),

                        TextInput::make('amount_usd')
                            ->label('Monto en USD')
                            ->numeric()
                            ->default(0.00)
                            ->live(debounce: 500) // 🔹 Evita que el número se borre mientras el usuario escribe
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $formattedState = (float) str_replace(',', '.', $state); // 🔹 Convierte ',' en '.' y fuerza a float
                                $set('amount_usd', number_format($formattedState, 2, '.', '')); // 🔹 Formatea con 2 decimales
            
                                self::calculateSubtotal($set, $get);
                                self::calculateGlobalTotals($set, $get);
                            })
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),

                        TextInput::make('amount_cop')
                            ->label('Monto en COP')
                            ->numeric()
                            ->default(0.00)
                            ->live(debounce: 500) // 🔹 Evita que el número se borre mientras el usuario escribe
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $formattedState = (float) str_replace(',', '.', $state); // 🔹 Convierte ',' en '.' y fuerza a float
                                $set('amount_cop', number_format($formattedState, 2, '.', '')); // 🔹 Formatea con 2 decimales
                                
                                self::calculateSubtotal($set, $get);
                                self::calculateGlobalTotals($set, $get);
                            })
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
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
            ->deleteAction(
                fn (Action $action) => $action->requiresConfirmation(),
            )
            ->disableItemCreation(fn() => !Auth::user()->hasRole('Tesorero Sectorial')) // 🔹 Oculta el botón "Reset" en el header del Repeat
            ->disableItemDeletion(fn() => !Auth::user()->hasRole('Tesorero Sectorial')) // 🔹 Solo el Tesorero Sectorial puede eliminar elementos del Repeat
            ->columns(1);
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
        $items = $get('../../offering_items') ?? [];

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

    



    
}