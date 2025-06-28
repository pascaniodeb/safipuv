<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChurchResource\Pages;
use App\Filament\Resources\ChurchResource\RelationManagers;
use Illuminate\Support\Facades\Auth;
use App\Models\Church;
use App\Models\Pastor;
use App\Models\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Traits\AccessControlTrait;
use App\Traits\Filters\HasUbicacionGeograficaFilters;
use App\Services\TerritorialFormService;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChurchResource extends Resource
{
    use AccessControlTrait;
    use HasUbicacionGeograficaFilters;
    
    protected static ?string $model = Church::class;

    protected static ?int $navigationSort = 3; // Orden

    public static function getPluralModelLabel(): string
    {
        return 'Iglesias'; // Texto personalizado para el título principal
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

    public static function getNavigationBadge(): ?string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::$model;

        return (string) $modelClass::count(); // Personaliza según sea necesario
    }
    
    protected static ?string $navigationIcon = 'heroicon-c-building-library';

    public function view(?User $user, Pastor $pastor): bool
    {
        return true; // Todos los usuarios pueden ver
    }

    public function update(?User $user, Pastor $pastor): bool
    {
        return $user && $user->hasAnyRole([
            'Secretario Nacional',
            'Secretario Regional',
            'Secretario Sectorial',
            'Tesorero Nacional',
            'Tesorero Sectorial',
            'Pastor',
        ]);
    }

    protected static function getTableRecordUrl(Model $record): ?string
    {
        if(auth()->user()?->can('update', $record)){
            return static::getUrl('edit', ['record' => $record]);
        }
        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery(); // 🔹 Usa la consulta base correctamente
    }

    public static function canViewNotifications(): bool
    {
        return true; // 🔹 Habilita la visualización de notificaciones
    }
    
    /*
    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole([
            'Administrador',
            'Secretario Nacional',
            'Secretario Regional',
            'Secretario Sectorial',
            'Tesorero Nacional',
            'Tesorero Regional',
            'Tesorero Sectorial',
        ]);
    }*/

    /**
     * Define the form schema for the Church resource.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Datos de la Iglesia')
                            ->schema(static::getDetailsFormSchema())
                            ->columns(['md' => 2]),

                        Forms\Components\Section::make('Datos de Membresía y Pastor')
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema(static::getMembresiaFormSchema())
                                    ->columns(['md' => 3]),

                                Forms\Components\Group::make()
                                    ->schema(static::getPastorFormSchema())
                                    ->columns(['md' => 3]),
                            ])
                    ])
                    ->columnSpan(['lg' => fn (?Church $record) => $record === null ? 3 : 2]),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Registrada el')
                            ->content(fn (Church $record): ?string => $record->created_at?->diffForHumans()),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Última actualización')
                            ->content(fn (Church $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Church $record) => $record === null),
            ])
            ->columns(3);
    }

    public static function getStepFundacionales(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->prefix('IPUV')
                ->maxLength(255)
                ->disabled(fn () => !Auth::user()->hasAnyRole([
                    'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                    'Secretario Regional', 'Secretario Sectorial',
                ]))
                ->dehydrated(),
                

            // 📅 Fecha de apertura
            Forms\Components\DatePicker::make('date_opening')
                ->label('Fecha de Apertura')
                ->required()
                ->native(false)
                ->reactive()
                ->disabled(fn () => !Auth::user()->hasAnyRole([
                    'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                    'Secretario Regional', 'Secretario Sectorial',
                ]))
                ->dehydrated(),

            // 🆔 Código de la iglesia (se llenará en el backend)
            Forms\Components\TextInput::make('code_church')
                ->label('Código de la Iglesia')
                ->disabled() // no editable
                //->required()
                ->dehydrated()
                ->unique(ignoreRecord: true),

                

            Forms\Components\TextInput::make('pastor_founding')
                ->label('Pastor Fundador')
                ->maxLength(255),
                

            Forms\Components\Radio::make('type_infrastructure')
                ->label('Tipo de Infraestructura')
                ->options([
                    'Propia' => 'Propia', 'Alquilada' => 'Alquilada', 'Prestada' => 'Prestada',
                    'Municipal' => 'Municipal', 'Condominio' => 'Condominio', 'INTI' => 'INTI',
                    'Invasión' => 'Invasión', 'Reservas' => 'Reservas', 'Baldío' => 'Baldío',
                ]),
                

            Forms\Components\Toggle::make('legalized')
                ->label('¿Legalizada?')
                ->required()
                ->reactive(),

            Forms\Components\TextInput::make('legal_entity_number')
                ->label('Número de Personería Jurídica')
                ->maxLength(55)
                ->visible(fn (callable $get) => $get('legalized')),

            Forms\Components\TextInput::make('number_rif')
                ->label('Número de RIF')
                ->maxLength(55)
                ->visible(fn (callable $get) => $get('legalized')),

            // Reemplaza tus 3 selects anteriores con esta línea:
            ...TerritorialFormService::getTerritorialComponents(),

            // Estado → Ciudad → Municipio → Parroquia
            Forms\Components\Select::make('state_id')
                ->label('Estado')
                ->native(false)
                ->options(fn () => \App\Models\State::pluck('name', 'id'))
                ->searchable()
                ->placeholder('Seleccione un estado...')
                ->reactive()
                ->required()
                ->afterStateUpdated(fn ($state, Set $set) => [
                    $set('city_id', null), $set('municipality_id', null), $set('parish_id', null),
                ]),
                

            Forms\Components\Select::make('city_id')
                ->label('Ciudad')
                ->native(false)
                ->options(fn (Get $get) =>
                    \App\Models\City::where('state_id', $get('state_id'))->pluck('name', 'id'))
                ->searchable()
                ->placeholder('Seleccione una ciudad...')
                ->reactive()
                ->required()
                ->disabled(fn (Get $get) => !$get('state_id'))
                ->afterStateUpdated(fn ($state, Set $set) => [
                    $set('municipality_id', null), $set('parish_id', null),
                ]),
                

            Forms\Components\Select::make('municipality_id')
                ->label('Municipio')
                ->native(false)
                ->options(fn (Get $get) =>
                    \App\Models\Municipality::where('state_id', $get('state_id'))->pluck('name', 'id'))
                ->searchable()
                ->placeholder('Seleccione un municipio...')
                ->reactive()
                ->required()
                ->disabled(fn (Get $get) => !$get('city_id'))
                ->afterStateUpdated(fn ($state, Set $set) => $set('parish_id', null)),
                

            Forms\Components\Select::make('parish_id')
                ->label('Parroquia')
                ->native(false)
                ->options(fn (Get $get) =>
                    \App\Models\Parish::where('municipality_id', $get('municipality_id'))->pluck('name', 'id'))
                ->searchable()
                ->placeholder('Seleccione una parroquia...')
                ->reactive()
                ->required()
                ->disabled(fn (Get $get) => !$get('municipality_id')),
                

            Forms\Components\Textarea::make('address')
                ->label('Dirección'),
                
        ];
    }

    public static function getStepMembresia(): array
    {
        return [
            Forms\Components\TextInput::make('adults')
                ->label('Adultos')
                ->numeric()
                ->default(0)
                ->reactive()
                ->lazy()
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $children = $get('children') ?? 0;
                    $members = $state + $children;
                    $set('members', $members);

                    $category = \App\Models\CategoryChurch::findCategoryByMembers($members);
                    $set('category_church_id', $category?->id);
                })
                ->disabled(fn () => !Auth::user()->hasAnyRole([
                    'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                    'Secretario Regional', 'Secretario Sectorial',
                ]))
                ->dehydrated(),

            Forms\Components\TextInput::make('children')
                ->label('Niños')
                ->numeric()
                ->default(0)
                ->reactive()
                ->lazy()
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $adults = $get('adults') ?? 0;
                    $members = $state + $adults;
                    $set('members', $members);

                    $category = \App\Models\CategoryChurch::findCategoryByMembers($members);
                    $set('category_church_id', $category?->id);
                })
                ->disabled(fn () => !Auth::user()->hasAnyRole([
                    'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                    'Secretario Regional', 'Secretario Sectorial',
                ]))
                ->dehydrated(),

            Forms\Components\TextInput::make('baptized')
                ->label('Bautizados')
                ->numeric()
                ->default(0),

            Forms\Components\TextInput::make('to_baptize')
                ->label('Por Bautizar')
                ->numeric()
                ->default(0),

            Forms\Components\TextInput::make('holy_spirit')
                ->label('Llenos del Espíritu Santo')
                ->numeric()
                ->default(0),

            Forms\Components\TextInput::make('groups_cells')
                ->label('Grupos de Células')
                ->numeric()
                ->default(0),

            Forms\Components\TextInput::make('centers_preaching')
                ->label('Centros de Predicación')
                ->numeric()
                ->default(0),

            Forms\Components\TextInput::make('members')
                ->label('Miembros')
                ->numeric()
                ->default(0)
                ->disabled()
                ->dehydrated(),

            Forms\Components\Select::make('category_church_id')
                ->label('Categoría de la Iglesia')
                ->relationship('categoryChurch', 'name')
                ->searchable()
                ->required()
                ->disabled()
                ->dehydrated()
                ->reactive(),

            Forms\Components\Toggle::make('directive_local')
                ->label('¿Directiva Local?')
                ->default(false),

            Forms\Components\Toggle::make('co_pastor')
                ->label('¿Co-Pastor?')
                ->default(false),

            Forms\Components\Toggle::make('professionals')
                ->label('¿Profesionales?')
                ->default(false)
                ->reactive(),

            Forms\Components\Section::make('Profesionales')
                ->schema([
                    Forms\Components\Repeater::make('name_professionals')
                        ->label('Nombres y Niveles Académicos')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre del Profesional')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('academic_level')
                                ->label('Nivel Académico')
                                ->options([
                                    'Técnico Superior Universitario (TSU)' => 'Técnico Superior Universitario (TSU)',
                                    'Licenciatura' => 'Licenciatura',
                                    'Ingeniería' => 'Ingeniería',
                                    'Especialización' => 'Especialización',
                                    'Maestría' => 'Maestría',
                                    'Doctorado' => 'Doctorado',
                                    'Otros' => 'Otros',
                                ])
                                ->required(),
                        ])
                        ->columns(2)
                        ->maxItems(8)
                        ->createItemButtonLabel('Agregar Profesional')
                        ->default([]),
                ])
                ->visible(fn (callable $get) => $get('professionals'))
                ->collapsible(),
        ];
    }

    public static function getStepPastor(): array
    {
        return [
            // Pastor Titular (se guarda en pastor_current y number_cedula)
            Forms\Components\Select::make('pastor_current_id')
                ->label('Cédula del Pastor Titular')
                ->options(function (callable $get) {
                    $sectorId = $get('sector_id');
                    
                    if (!$sectorId) {
                        return [];
                    }
                    
                    // Pastores titulares (tipos 1 y 4) del sector
                    $pastorIds = \App\Models\PastorMinistry::whereIn('pastor_type_id', [1, 4])
                        ->where('sector_id', $sectorId)
                        ->pluck('pastor_id');
                    
                    // Excluir pastores cuya cédula ya está asignada en churches.number_cedula
                    $assignedCedulas = \App\Models\Church::whereNotNull('number_cedula')
                        ->where('number_cedula', '!=', '')
                        ->pluck('number_cedula');
                    
                    $availablePastors = \App\Models\Pastor::whereIn('id', $pastorIds)
                        ->whereNotIn('number_cedula', $assignedCedulas)
                        ->get();
                    
                    return $availablePastors->pluck('number_cedula', 'id');
                })
                ->searchable()
                ->reactive()
                ->placeholder('Buscar por cédula del sector')
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('pastor_current', null);
                    $set('number_cedula', null);
                    $set('email', null);
                    $set('phone', null);

                    if ($state) {
                        $pastor = \App\Models\Pastor::find($state);
                        if ($pastor) {
                            $set('pastor_current', $pastor->name . ' ' . $pastor->lastname);
                            $set('number_cedula', $pastor->number_cedula);
                            $set('email', $pastor->email);
                            $set('phone', $pastor->phone_mobile ?? $pastor->phone_house);
                        }
                    }
                })
                ->disabled(fn () => !Auth::user()->hasAnyRole([
                    'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                    'Secretario Regional', 'Secretario Sectorial',
                ]))
                ->dehydrated(),

            Forms\Components\TextInput::make('pastor_current')
                ->label('Nombre del Pastor Titular')
                ->reactive()
                ->disabled()
                ->dehydrated(),

            Forms\Components\TextInput::make('number_cedula')
                ->label('Cédula del Pastor Titular')
                ->reactive()
                ->disabled()
                ->dehydrated(),

            Forms\Components\Select::make('current_position_id')
                ->label('Posición Actual')
                ->relationship('currentPosition', 'name')
                ->searchable()
                ->reactive()
                ->placeholder('Selecciona una posición')
                ->disabled(fn () => !Auth::user()->hasAnyRole([
                    'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                    'Superintendente Regional', 'Secretario Regional', 'Tesorero Regional',
                    'Supervisor Distrital', 'Presbítero Sectorial', 'Secretario Sectorial',
                    'Tesorero Sectorial',
                ]))
                ->dehydrated(),

            Forms\Components\TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->reactive()
                ->disabled()
                ->dehydrated(),

            Forms\Components\TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->reactive()
                ->disabled()
                ->dehydrated(),

            // Pastor Adjunto (se guarda en name_pastor_attach)
            Forms\Components\Toggle::make('pastor_attach')
                ->label('¿Asignar Pastor Adjunto?')
                ->default(false)
                ->reactive()
                ->disabled(function (callable $get) {
                    $members = $get('members') ?? 0;
                    $hasPermission = Auth::user()->hasAnyRole([
                        'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                        'Secretario Regional', 'Secretario Sectorial',
                    ]);
                    
                    return !$hasPermission || $members < 51;
                })
                ->helperText(function (callable $get) {
                    $members = $get('members') ?? 0;
                    if ($members < 51) {
                        return '⚠️ Se requieren al menos 51 miembros para asignar Pastor Adjunto (Actual: ' . $members . ')';
                    }
                    return 'Disponible para iglesias con 51+ miembros';
                })
                ->dehydrated(),

            Forms\Components\Select::make('adjunct_pastor_select')
                ->label('Seleccionar Pastor Adjunto')
                ->options(function (callable $get) {
                    $sectorId = $get('sector_id');
                    $members = $get('members') ?? 0;
                    
                    if (!$sectorId || $members < 51) {
                        return [];
                    }
                    
                    // Pastores adjuntos (tipo 2) del sector
                    $pastorIds = \App\Models\PastorMinistry::where('pastor_type_id', 2)
                        ->where('sector_id', $sectorId)
                        ->pluck('pastor_id');
                    
                    // Obtener nombres ya asignados como adjuntos
                    $assignedNames = \App\Models\Church::whereNotNull('name_pastor_attach')
                        ->where('name_pastor_attach', '!=', '')
                        ->pluck('name_pastor_attach');
                    
                    // Mostrar pastores disponibles
                    return \App\Models\Pastor::whereIn('id', $pastorIds)
                        ->get()
                        ->filter(function ($pastor) use ($assignedNames) {
                            $fullName = $pastor->name . ' ' . $pastor->lastname;
                            return !$assignedNames->contains($fullName);
                        })
                        ->pluck('number_cedula', 'id');
                })
                ->searchable()
                ->reactive()
                ->placeholder('Buscar por cédula del sector')
                ->visible(fn (callable $get) => $get('pastor_attach'))
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('name_pastor_attach', null);

                    if ($state) {
                        $pastor = \App\Models\Pastor::find($state);
                        if ($pastor) {
                            $set('name_pastor_attach', $pastor->name . ' ' . $pastor->lastname);
                        }
                    }
                })
                ->disabled(fn () => !Auth::user()->hasAnyRole([
                    'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                    'Secretario Regional', 'Secretario Sectorial',
                ]))
                ->dehydrated(false), // No guardar este campo, solo es para selección

            Forms\Components\TextInput::make('name_pastor_attach')
                ->label('Nombre del Pastor Adjunto')
                ->reactive()
                ->disabled()
                ->dehydrated()
                ->visible(fn (callable $get) => $get('pastor_attach')),

            // Pastor Asistente (se guarda en name_pastor_assistant)
            Forms\Components\Toggle::make('pastor_assistant')
                ->label('¿Asignar Pastor Asistente?')
                ->default(false)
                ->reactive()
                ->disabled(function (callable $get) {
                    $members = $get('members') ?? 0;
                    $hasPermission = Auth::user()->hasAnyRole([
                        'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                        'Secretario Regional', 'Secretario Sectorial',
                    ]);
                    
                    return !$hasPermission || $members < 101;
                })
                ->helperText(function (callable $get) {
                    $members = $get('members') ?? 0;
                    if ($members < 101) {
                        return '⚠️ Se requieren al menos 101 miembros para asignar Pastor Asistente (Actual: ' . $members . ')';
                    }
                    return 'Disponible para iglesias con 101+ miembros';
                })
                ->dehydrated(),

            Forms\Components\Select::make('assistant_pastor_select')
                ->label('Seleccionar Pastor Asistente')
                ->options(function (callable $get) {
                    $sectorId = $get('sector_id');
                    $members = $get('members') ?? 0;
                    
                    if (!$sectorId || $members < 101) {
                        return [];
                    }
                    
                    // Pastores asistentes (tipo 3) del sector
                    $pastorIds = \App\Models\PastorMinistry::where('pastor_type_id', 3)
                        ->where('sector_id', $sectorId)
                        ->pluck('pastor_id');
                    
                    // Obtener nombres ya asignados como asistentes
                    $assignedNames = \App\Models\Church::whereNotNull('name_pastor_assistant')
                        ->where('name_pastor_assistant', '!=', '')
                        ->pluck('name_pastor_assistant');
                    
                    // Mostrar pastores disponibles
                    return \App\Models\Pastor::whereIn('id', $pastorIds)
                        ->get()
                        ->filter(function ($pastor) use ($assignedNames) {
                            $fullName = $pastor->name . ' ' . $pastor->lastname;
                            return !$assignedNames->contains($fullName);
                        })
                        ->pluck('number_cedula', 'id');
                })
                ->searchable()
                ->reactive()
                ->placeholder('Buscar por cédula del sector')
                ->visible(fn (callable $get) => $get('pastor_assistant'))
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('name_pastor_assistant', null);

                    if ($state) {
                        $pastor = \App\Models\Pastor::find($state);
                        if ($pastor) {
                            $set('name_pastor_assistant', $pastor->name . ' ' . $pastor->lastname);
                        }
                    }
                })
                ->disabled(fn () => !Auth::user()->hasAnyRole([
                    'Administrador', 'Secretario Nacional', 'Tesorero Nacional',
                    'Secretario Regional', 'Secretario Sectorial',
                ]))
                ->dehydrated(false), // No guardar este campo, solo es para selección

            Forms\Components\TextInput::make('name_pastor_assistant')
                ->label('Nombre del Pastor Asistente')
                ->reactive()
                ->disabled()
                ->dehydrated()
                ->visible(fn (callable $get) => $get('pastor_assistant')),
        ];
    }

    
    /**
     * Define the table schema for the Church resource.
     */
    public static function table(Table $table): Table
    {
        
        
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_opening')
                    ->label('Fecha de Apertura')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pastor_founding')
                    ->label('Pastor Fundador')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('code_church')
                    ->label('Código')
                    ->searchable(),

                Tables\Columns\TextColumn::make('categoryChurch.name')
                    ->label('Categoría')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type_infrastructure')
                    ->label('Tipo de Infraestructura')
                    ->colors([
                        'primary' => 'Propia',
                        'warning' => 'Alquilada',
                        'success' => 'Municipal',
                        'danger' => 'Invasión',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('legalized')
                    ->label('¿Legalizada?')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('number_rif')
                    ->label('Número de RIF')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('region.name')
                    ->label('Región')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('Distrito')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sector.name')
                    ->label('Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Ciudad')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pastor_current')
                    ->label('Pastor Actual')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('number_cedula')
                    ->label('Cédula')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('currentPosition.name')
                    ->label('Posición Actual')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtros personalizados
                ...self::getUbicacionGeograficaFilters(),
            ])
            ->actions([
                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->label(false)
                    ->tooltip('Ver Iglesia')
                    ->color('primary')
                    ->size('md')
                    ->modalHeading(fn ($record) => 'Detalles de: ' . $record->name),

                EditAction::make()
                    ->icon('heroicon-s-pencil-square')    
                    ->label(false)
                    ->tooltip('Editar Iglesia')
                    ->color('warning')
                    ->size('md')
                    ->visible(function ($record) {
                        $user = auth()->user();
                        
                        // Si el usuario tiene alguno de los roles privilegiados, puede editar cualquier registro
                        if ($user?->hasAnyRole([
                            'Secretario Nacional',
                            'Secretario Regional',
                            'Secretario Sectorial',
                            'Tesorero Nacional',
                            'Tesorero Sectorial',
                        ])) {
                            return true;
                        }
                        
                        // Si no tiene roles privilegiados, solo puede editar su propio registro
                        // Comparamos el username del usuario autenticado con el number_cedula del pastor
                        return $user && $record && $user->username === $record->number_cedula;
                    }),
            ])
            ->recordUrl(function ($record) {
                $user = auth()->user();

                // Solo roles administrativos pueden redirigir directamente a la edición
                if ($user?->hasAnyRole([
                    'Administrador',
                    'Secretario Nacional',
                    'Secretario Regional',
                    'Secretario Sectorial',
                    'Supervisor Distrital',
                ])) {
                    return ChurchResource::getUrl('edit', ['record' => $record]);
                }

                return null;
            })
            ->defaultSort('name', 'asc')
            
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar Iglesias Seleccionadas')
                        ->visible(function () {
                            $user = auth()->user();
                            return $user?->hasAnyRole([
                                'Administrador',
                                'Tesorero Nacional',
                            ]);
                        }),
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
            'index' => Pages\ListChurches::route('/'),
            'create' => Pages\CreateChurch::route('/create'),
            'edit' => Pages\EditChurch::route('/{record}/edit'),
        ];
    }

    /** @return Forms\Components\Component[] */
    public static function getDetailsFormSchema(): array
    {
        return self::getStepFundacionales(); // o copia aquí directamente si no usas Wizard
    }

    public static function getMembresiaFormSchema(): array
    {
        return self::getStepMembresia(); // ya extraído o por extraer
    }

    public static function getPastorFormSchema(): array
    {
        return self::getStepPastor(); // ya extraído o por extraer
    }



}