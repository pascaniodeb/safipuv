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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChurchResource extends Resource
{
    use AccessControlTrait;
    
    protected static ?string $model = Church::class;

    protected static ?int $navigationSort = 3; // Orden

    public static function getEloquentQuery(): Builder
    {
        // Aplica la lógica de control de acceso al recurso
        return static::scopeAccessControlQuery(parent::getEloquentQuery());
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

    public static function getPluralModelLabel(): string
    {
        return 'Iglesias'; // Texto personalizado para el título principal
    }

    protected static ?string $navigationIcon = 'heroicon-c-building-library';

    public static function getNavigationBadge(): ?string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::$model;

        return (string) $modelClass::count(); // Personaliza según sea necesario
    }

    
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Datos Fundacionales')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->prefix('IPUV')
                                ->maxLength(255)
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),

                            Forms\Components\DatePicker::make('date_opening')
                                ->label('Fecha de Apertura')
                                ->required()
                                ->reactive() // Permite reaccionar a cambios en el estado
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        // Obtener fecha y descomponerla
                                        $month = str_pad(now()->parse($state)->format('m'), 2, '0', STR_PAD_LEFT);
                                        $year = now()->parse($state)->format('Y');

                                        // Obtener el último código generado y calcular el siguiente
                                        $lastCode = \App\Models\Church::latest('id')->value('code_church');
                                        $lastIncrement = $lastCode ? intval(substr($lastCode, -4)) : 0;
                                        $nextIncrement = str_pad($lastIncrement + 1, 4, '0', STR_PAD_LEFT);

                                        // Generar el nuevo código
                                        $code = "M{$month}A{$year}C{$nextIncrement}";

                                        // Establecer el valor del campo "code_church"
                                        $set('code_church', $code);
                                    }
                                })
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),

                            Forms\Components\TextInput::make('pastor_founding')
                                ->label('Pastor Fundador')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('code_church')
                                ->label('Código de la Iglesia')
                                ->required()
                                ->disabled() // Deshabilitado para que no sea editable
                                ->maxLength(255),

                            Forms\Components\Radio::make('type_infrastructure')
                                ->label('Tipo de Infraestructura')
                                ->options([
                                    'Propia' => 'Propia',
                                    'Alquilada' => 'Alquilada',
                                    'Prestada' => 'Prestada',
                                    'Municipal' => 'Municipal',
                                    'Condominio' => 'Condominio',
                                    'INTI' => 'INTI',
                                    'Invasión' => 'Invasión',
                                    'Reservas' => 'Reservas',
                                    'Baldío' => 'Baldío',
                                ])
                                ->columns(3),


                            Forms\Components\Toggle::make('legalized')
                                ->label('¿Legalizada?')
                                ->required()
                                ->reactive(), // Asegura que el campo reaccione a los cambios


                            Forms\Components\TextInput::make('legal_entity_number')
                                ->label('Número de Personería Jurídica')
                                ->maxLength(55)
                                ->visible(fn (callable $get) => $get('legalized')), // Visible solo si legalized es true

                            Forms\Components\TextInput::make('number_rif')
                                ->label('Número de RIF')
                                ->maxLength(55)
                                ->visible(fn (callable $get) => $get('legalized')), // Visible solo si legalized es true

                            Forms\Components\Select::make('region_id')
                                ->label('Región')
                                ->relationship('region', 'name')
                                ->required()
                                ->reactive()
                                ->native(false)
                                ->afterStateUpdated(fn (callable $set) => $set('district_id', null))
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),

                            Forms\Components\Select::make('district_id')
                                ->label('Distrito')
                                ->options(fn (callable $get) =>
                                    \App\Models\District::where('region_id', $get('region_id'))->pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->native(false)
                                ->afterStateUpdated(fn (callable $set) => $set('sector_id', null))
                                ->disabled(fn (callable $get) => !$get('region_id'))
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),

                            Forms\Components\Select::make('sector_id')
                                ->label('Sector')
                                ->options(fn (callable $get) =>
                                    \App\Models\Sector::where('district_id', $get('district_id'))->pluck('name', 'id'))
                                ->required()
                                ->native(false)
                                ->disabled(fn (callable $get) => !$get('district_id'))
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),

                            Forms\Components\Select::make('state_id')
                                ->label('Estado')
                                ->relationship('state', 'name')
                                ->required()
                                ->reactive() // Marca el campo como reactivo
                                ->native(false)
                                ->afterStateUpdated(fn (callable $set) => $set('city_id', null)),

                            Forms\Components\Select::make('city_id')
                                ->label('Municipio')
                                ->options(function (callable $get) {
                                    $stateId = $get('state_id'); // Obtén el estado seleccionado
                                    if (!$stateId) {
                                        return []; // Si no hay estado seleccionado, devuelve una lista vacía
                                    }
                                    return \App\Models\City::where('state_id', $stateId)->pluck('name', 'id'); // Filtra los municipios por el estado seleccionado
                                })
                                ->required()
                                ->native(false)
                                ->disabled(fn (callable $get) => !$get('state_id')),

                            Forms\Components\Textarea::make('address')
                                ->label('Dirección')
                                ->columnSpanFull(),
                        ])
                        ->columns(2), // Dos columnas para los campos de esta sección

                    Forms\Components\Wizard\Step::make('Datos de Membresía')
                        ->schema([

                            Forms\Components\TextInput::make('adults')
                                ->label('Adultos')
                                ->numeric()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $children = $get('children') ?? 0;
                                    $members = $state + $children;
                                    $set('members', $members);

                                    // Actualizar la categoría basada en el número de miembros
                                    $category = \App\Models\CategoryChurch::findCategoryByMembers($members);
                                    if ($category) {
                                        $set('category_church_id', $category->id);
                                    } else {
                                        $set('category_church_id', null);
                                    }
                                })
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),

                            Forms\Components\TextInput::make('children')
                                ->label('Niños')
                                ->numeric()
                                ->default(0)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $adults = $get('adults') ?? 0;
                                    $members = $state + $adults;
                                    $set('members', $members);

                                    // Actualizar la categoría basada en el número de miembros
                                    $category = \App\Models\CategoryChurch::findCategoryByMembers($members);
                                    if ($category) {
                                        $set('category_church_id', $category->id);
                                    } else {
                                        $set('category_church_id', null);
                                    }
                                })
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
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
                                ->disabled() // Campo deshabilitado para evitar edición manual
                                ->dehydrated(),


                            Forms\Components\Select::make('category_church_id')
                                ->label('Categoría de la Iglesia')
                                ->relationship('categoryChurch', 'name')
                                ->searchable()
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->reactive(), // Asegura que el campo reaccione a los cambios

                            Forms\Components\Toggle::make('directive_local')
                                ->label('¿Directiva Local?')
                                ->default(false),

                            Forms\Components\Toggle::make('co_pastor')
                                ->label('¿Co-Pastor?')
                                ->default(false),

                            Forms\Components\Toggle::make('professionals')
                                ->label('¿Profesionales?')
                                ->default(false)
                                ->reactive(), // Habilita la reactividad para cambios dinámicos

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
                                        ->columns(2) // Organiza los campos en dos columnas
                                        ->maxItems(8) // Límite de 8 profesionales
                                        ->createItemButtonLabel('Agregar Profesional') // Texto del botón de agregar
                                        ->default([]), // Asegura que el campo inicie vacío
                                ])
                                ->visible(fn (callable $get) => $get('professionals')) // Visible solo si el Toggle está activo
                                ->collapsible(), // Permite colapsar la sección si hay muchos elementos

                        ])
                        ->columns(3), // Tres columnas para los campos de esta sección

                    Forms\Components\Wizard\Step::make('Datos del Pastor')
                        ->schema([

                            Forms\Components\Select::make('pastor_current_id')
                                ->label('Cédula del Pastor Actual')
                                ->options(function () {
                                    $user = Auth::user();

                                    // Consulta base: Todos los pastores
                                    $query = \App\Models\Pastor::query();

                                    // Aplicar filtros según el rol del usuario
                                    if ($user->hasAnyRole(['Administrador', 'Secretario Nacional', 'Tesorero Nacional'])) {
                                        // Roles nacionales ven todas las cédulas, incluidos los pastores titulares
                                        return $query->pluck('number_cedula', 'id');
                                    }

                                    if ($user->hasRole('Pastor')) {
                                        // Un pastor estándar solo puede ver su propia cédula
                                        $pastor = $user->pastor; // Obtener el pastor relacionado con el usuario
                                        if ($pastor) {
                                            return [$pastor->id => $pastor->number_cedula];
                                        }
                                        return [];
                                    }

                                    // Excluir pastores titulares asignados a otras iglesias para roles regionales, distritales y sectoriales
                                    $query->whereDoesntHave('ministries', function ($q) {
                                        $q->where('pastor_type_id', 1); // Excluir pastores ya asignados como titulares
                                    });

                                    // Obtener la jurisdicción del usuario
                                    $jurisdiccion = $user->jurisdiccion;
                                    if ($jurisdiccion) {
                                        if ($user->hasAnyRole(['Secretario Regional'])) {
                                            // Roles regionales ven pastores en su región
                                            $query->where('region_id', $jurisdiccion->id);
                                        } elseif ($user->hasAnyRole(['Secretario Distrital'])) {
                                            // Roles distritales ven pastores en su distrito
                                            $query->where('district_id', $jurisdiccion->id);
                                        } elseif ($user->hasAnyRole(['Secretario Sectorial'])) {
                                            // Roles sectoriales ven pastores en su sector
                                            $query->where('sector_id', $jurisdiccion->id);
                                        }
                                    }

                                    // Devolver las cédulas filtradas
                                    return $query->pluck('number_cedula', 'id');
                                })
                                ->searchable()
                                ->reactive()
                                ->placeholder('Buscar por cédula')
                                ->afterStateUpdated(function ($state, callable $set) {
                                    // Limpiar todos los campos al iniciar o cuando se borre el campo
                                    $set('pastor_current', null);
                                    $set('number_cedula', null);
                                    $set('email', null);
                                    $set('phone', null);

                                    if ($state) {
                                        // Buscar el pastor seleccionado
                                        $pastor = \App\Models\Pastor::find($state);
                                        if ($pastor) {
                                            // Verificar si el pastor ya está asignado como titular en otra iglesia
                                            $alreadyAssigned = \App\Models\PastorMinistry::where('pastor_id', $state)
                                                ->where('pastor_type_id', 1) // Tipo 1: Pastor Titular
                                                ->exists();
                                            if ($alreadyAssigned) {
                                                // Si ya está asignado, mostrar una notificación de advertencia
                                                Notification::make()
                                                    ->title('Advertencia')
                                                    ->body('Este pastor ya está asignado como Pastor Titular en otra iglesia.')
                                                    ->warning()
                                                    ->send();
                                            }

                                            // Llenar los campos automáticamente con los datos del pastor
                                            $set('pastor_current', $pastor->name . ' ' . $pastor->lastname);
                                            $set('number_cedula', $pastor->number_cedula);
                                            $set('email', $pastor->email);
                                            $set('phone', $pastor->phone_mobile ?? $pastor->phone_house);
                                        }
                                    }
                                }),
                                




                            Forms\Components\TextInput::make('pastor_current')
                                ->label('Nombre del Pastor Actual')
                                ->reactive()
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\TextInput::make('number_cedula')
                                ->label('Cédula del Pastor Actual')
                                ->reactive()
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\Select::make('current_position_id')
                                ->label('Posición Actual')
                                ->relationship('currentPosition', 'name') // Relación con el modelo CurrentPosition
                                ->searchable()
                                ->reactive()
                                ->placeholder('Selecciona una posición') // Mensaje por defecto
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Distrital',
                                        'Secretario Sectorial',
                                    ]);
                                })
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


                            Forms\Components\Toggle::make('pastor_attach')
                                ->label('¿Asignar Pastor Adjunto?')
                                ->default(false)
                                ->reactive()
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),
                            
                            Forms\Components\Select::make('adjunct_pastor_id')
                                ->label('Seleccionar Pastor Adjunto')
                                ->options(function () {
                                    return \App\Models\Pastor::whereHas('churches', function ($query) {
                                        $query->where('church_pastor.pastor_type_id', 2); // Solo pastores con pastor_type_id = 2 (Adjunto)
                                    })->pluck('number_cedula', 'id'); // Mostrar solo los pastores con número de cédula
                                })
                                ->searchable()
                                ->reactive()
                                ->placeholder('Buscar por cédula')
                                ->visible(fn (callable $get) => $get('pastor_attach'))
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $pastor = \App\Models\Pastor::find($state);
                            
                                        if ($pastor) {
                                            // Llenar los campos automáticamente con el nombre y apellido del pastor adjunto
                                            $set('adjunct_name', $pastor->name);
                                            $set('adjunct_lastname', $pastor->lastname);
                            
                                            // Validar si el pastor ya está asignado como Pastor Adjunto en otra iglesia
                                            $alreadyAssigned = \App\Models\Church::whereHas('pastors', function ($query) use ($state) {
                                                $query->where('pastor_id', $state)->where('church_pastor.pastor_type_id', 2); // Tipo 2: Pastor Adjunto
                                            })->exists();
                            
                                            if ($alreadyAssigned) {
                                                $set('adjunct_pastor_id', null); // Limpiar la selección
                                                $set('adjunct_name', null); // Limpiar el nombre
                                                $set('adjunct_lastname', null); // Limpiar el apellido
                                                session()->flash('error', 'Este pastor ya está asignado como Pastor Adjunto en otra iglesia.');
                                            }
                                        }
                                    } else {
                                        // Si el estado es null (es decir, cuando el campo se limpia), limpiar los campos
                                        $set('adjunct_name', null);
                                        $set('adjunct_lastname', null);
                                    }
                                })
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('adjunct_name')
                                ->label('Nombre del Pastor Adjunto')
                                ->reactive()
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn (callable $get) => $get('pastor_attach')),
                            
                            Forms\Components\TextInput::make('adjunct_lastname')
                                ->label('Apellido del Pastor Adjunto')
                                ->reactive()
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn (callable $get) => $get('pastor_attach')),
                            


                            Forms\Components\Toggle::make('pastor_assistant')
                                ->label('¿Asignar Pastor Asistente?')
                                ->default(false)
                                ->reactive()
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),
                            
                            Forms\Components\Select::make('assistant_pastor_id')
                                ->label('Seleccionar Pastor Asistente')
                                ->options(function () {
                                    return \App\Models\Pastor::whereHas('churches', function ($query) {
                                        $query->where('church_pastor.pastor_type_id', 3); // Solo pastores con pastor_type_id = 3 (Asistente)
                                    })->pluck('number_cedula', 'id'); // Mostrar solo los pastores con número de cédula
                                })
                                ->searchable()
                                ->reactive()
                                ->placeholder('Buscar por cédula')
                                ->visible(fn (callable $get) => $get('pastor_assistant'))
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $pastor = \App\Models\Pastor::find($state);
                            
                                        if ($pastor) {
                                            // Llenar los campos automáticamente con el nombre y apellido del pastor asistente
                                            $set('assistant_name', $pastor->name);
                                            $set('assistant_lastname', $pastor->lastname);
                            
                                            // Validar si el pastor ya está asignado como Pastor Asistente a otra iglesia
                                            $alreadyAssigned = \App\Models\Church::whereHas('pastors', function ($query) use ($state) {
                                                $query->where('pastor_id', $state)->where('church_pastor.pastor_type_id', 3); // Tipo 3: Pastor Asistente
                                            })->exists();
                            
                                            if ($alreadyAssigned) {
                                                $set('assistant_pastor_id', null); // Limpiar la selección
                                                $set('assistant_name', null); // Limpiar el nombre
                                                $set('assistant_lastname', null); // Limpiar el apellido
                                                session()->flash('error', 'Este pastor ya está asignado como Pastor Asistente en otra iglesia.');
                                            }
                                        }
                                    } else {
                                        // Si el estado es null (es decir, cuando el campo se limpia), limpiar los campos
                                        $set('assistant_name', null);
                                        $set('assistant_lastname', null);
                                    }
                                })
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                        'Secretario Regional',
                                        'Secretario Sectorial', 
                                    ]);
                                })
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('assistant_name')
                                ->label('Nombre del Pastor Asistente')
                                ->reactive()
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn (callable $get) => $get('pastor_assistant')),
                            
                            Forms\Components\TextInput::make('assistant_lastname')
                                ->label('Apellido del Pastor Asistente')
                                ->reactive()
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn (callable $get) => $get('pastor_assistant')),
                            

                        ])
                        ->columns(3), // Tres columnas para los campos de esta sección

                ])
                ->columnSpanFull(), // El wizard ocupa todo el ancho del formulario
            ]);
    }

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
                //
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
            'index' => Pages\ListChurches::route('/'),
            'create' => Pages\CreateChurch::route('/create'),
            'edit' => Pages\EditChurch::route('/{record}/edit'),
        ];
    }
}