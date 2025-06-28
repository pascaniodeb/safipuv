<?php

namespace App\Filament\Resources\PastorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Pastor;
use App\Models\Church;
use App\Services\PastorLicenceService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\Concerns\InteractsWithTable;
use Filament\Resources\RelationManagers\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Traits\RelationManagerAccess;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MinistryRelationManager extends RelationManager
{
    use RelationManagerAccess;
    
    protected static string $relationship = 'pastorMinistries';

    protected static ?string $recordTitleAttribute = 'code_pastor';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return 'Información Ministerial'; // Título personalizado del encabezado
    }

    protected function getTableQuery(): Builder
    {
        $pastor = $this->getOwnerRecord();
        if (!$pastor) {
            throw new \Exception('El registro del pastor no está definido.');
        }

        // Obtener el modelo relacionado a través de la relación
        $relatedModel = $pastor->pastorMinistries()->getRelated();

        // Construir la consulta manualmente
        return $relatedModel->newQuery()->where('pastor_id', $pastor->id);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return Pastor::query()
            ->when($user->hasAnyRole(['Secretario Nacional', 'Tesorero Nacional', 'Administrador']), function ($query) {
                // Mostrar todos los pastores para roles nacionales
            })
            ->when($user->hasRole('Tesorero Regional'), function ($query) use ($user) {
                // Mostrar pastores de la región del usuario
                $query->where('region_id', $user->region_id); 
            })
            ->when($user->hasRole('Supervisor Distrital'), function ($query) use ($user) {
                // Mostrar pastores del distrito del usuario
                $query->where('district_id', $user->district_id); 
            })
            ->when($user->hasRole('Tesorero Sectorial'), function ($query) use ($user) {
                // Mostrar pastores del sector del usuario
                $query->where('sector_id', $user->sector_id); 
            })
            ->when($user->hasRole('Pastor'), function ($query) use ($user) {
                // Mostrar solo la información del pastor actual
                $query->where('id', $user->id);
            })
            ->when(!$user->hasAnyRole(['Secretario Nacional', 'Tesorero Nacional', 'Administrador', 'Tesorero Regional', 'Supervisor Distrital', 'Tesorero Sectorial', 'Pastor']), function ($query) {
                // No mostrar nada a otros usuarios
                $query->whereNull('id');
            });
    }
    
    

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                    


                    Forms\Components\TextInput::make('code_pastor')
                        ->label('Código del Pastor')
                        ->numeric()
                        ->required()
                        ->default(function () {
                            $pastor = $this->getOwnerRecord(); // Obtén el pastor relacionado
                            if (!$pastor) {
                                return null;
                            }
                    
                            // 🔹 1. Obtener los últimos 4 dígitos del número de cédula
                            $lastFourCedula = substr($pastor->number_cedula, -4);
                    
                            // 🔹 2. Obtener el año del campo start_date_ministry
                            $ministryYear = $pastor->start_date_ministry?->format('Y');
                    
                            // 🔹 3. Calcular el número incremental global para el año
                            $incrementable = \App\Models\Pastor::whereYear('start_date_ministry', $ministryYear)
                                ->withoutGlobalScopes() // ✅ Ignorar cualquier restricción de visibilidad de usuario
                                ->count() + 1; // ✅ Contar todos los pastores registrados en el mismo año y sumar 1
                    
                            // 🔹 4. Formatear el número incremental con 4 dígitos
                            $incrementable = str_pad($incrementable, 4, '0', STR_PAD_LEFT);
                    
                            // 🔹 5. Generar el código completo
                            return $lastFourCedula . $ministryYear . $incrementable;
                        })
                        ->maxLength(12)
                        ->minLength(12)
                        ->rule('digits:12')
                        ->dehydrateStateUsing(fn ($state, $record) => $record ? $record->code_pastor : $state)
                        ->disabled()
                        ->dehydrated(),
                

                
                    Forms\Components\Select::make('pastor_income_id')
                        ->label('Ingreso Pastoral')
                        ->relationship('pastorIncome', 'name')
                        ->native(false)
                        ->placeholder('Selecciona un ingreso')
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            try {
                                $pastor = $this->ownerRecord;
                        
                                // Validación fuerte de existencia y contenido
                                if (! $pastor || blank($pastor->start_date_ministry)) {
                                    return;
                                }
                        
                                $incomeId = $get('pastor_income_id');
                                $typeId = $get('pastor_type_id');
                        
                                // Verificamos que ambos campos estén llenos
                                if (blank($incomeId) || blank($typeId)) {
                                    return;
                                }
                        
                                $licenceId = \App\Services\PastorAssignmentService::determineLicence(
                                    $incomeId,
                                    $typeId,
                                    $pastor->start_date_ministry
                                );
                        
                                $set('pastor_licence_id', $licenceId);
                        
                            } catch (\Throwable $e) {
                                \Log::error('Error en afterStateUpdated en Relation Manager Ministry: ' . $e->getMessage());
                            }
                        })
                        ->disabled(fn () => !Auth::user()->hasAnyRole([
                            'Administrador',
                            'Secretario Nacional',
                            'Tesorero Nacional',
                            'Secretario Regional',
                            'Secretario Sectorial',
                        ]))
                        ->dehydrated(),
                    
                
                    Forms\Components\Select::make('pastor_type_id')
                        ->label('Tipo de Pastor')
                        ->relationship('pastorType', 'name')
                        ->native(false)
                        ->placeholder('Seleccione un tipo de pastor')
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            // Obtenemos el Pastor padre (ownerRecord) para acceder a la fecha de inicio
                            $pastor = $this->ownerRecord;
                    
                            // Si por alguna razón no existe la fecha, retornamos o usamos un valor por defecto
                            if (! $pastor->start_date_ministry) {
                                return;
                            }
                    
                            // Llamamos a PastorAssignmentService::determineLicence
                            $licenceId = \App\Services\PastorAssignmentService::determineLicence(
                                $get('pastor_income_id'),
                                $get('pastor_type_id'),
                                $pastor->start_date_ministry
                            );
                    
                            // Asignamos el resultado al campo 'pastor_licence_id'
                            $set('pastor_licence_id', $licenceId);
                        })
                        ->disabled(fn () => !Auth::user()->hasAnyRole([
                            'Administrador',
                            'Secretario Nacional',
                            'Tesorero Nacional',
                            'Secretario Regional',
                            'Secretario Sectorial', 
                        ]))
                        ->dehydrated(),
                    
                

    
                    Forms\Components\Select::make('church_id')
                        ->label('Iglesia Asociada')
                        ->native(false)
                        ->options(\App\Models\Church::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Selecciona una iglesia')
                        ->reactive()
                        ->nullable()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state) {
                                // 🔍 Buscar la iglesia seleccionada
                                $church = \App\Models\Church::find($state);
                    
                                // ❌ Validar que la iglesia exista
                                if (!$church) {
                                    $set('church_id', null);
                                    Notification::make()
                                        ->title('Error')
                                        ->body('La iglesia seleccionada no existe.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                    
                                // 🔍 Obtener el tipo de pastor seleccionado
                                $pastorTypeId = $get('pastor_type_id'); // ✅ Obtener el ID del tipo de pastor
                    
                                // 🔹 Verificar la cantidad de pastores asignados por tipo
                                $pastorCounts = $church->pastorMinistries()
                                    ->selectRaw('pastor_type_id, COUNT(*) as count')
                                    ->groupBy('pastor_type_id')
                                    ->pluck('count', 'pastor_type_id');
                    
                                // 🔹 Definir los límites máximos permitidos
                                $maxPastorsByType = [
                                    1 => 1, // ✅ 1 Pastor Titular
                                    2 => 1, // ✅ 1 Pastor Adjunto
                                    3 => 1, // ✅ 1 Pastor Asistente
                                    4 => 1, // ✅ 1 Pastora Titular
                                ];
                    
                                // ❌ Si el tipo de pastor ya alcanzó el máximo, bloquear la asignación
                                if (isset($maxPastorsByType[$pastorTypeId]) && ($pastorCounts[$pastorTypeId] ?? 0) >= $maxPastorsByType[$pastorTypeId]) {
                                    $set('church_id', null);
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Esta iglesia ya tiene el máximo permitido de este tipo de pastor.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                    
                                // ✅ Asignar los campos relacionados con la iglesia
                                $set('code_church', $church->code_church);
                                $set('region_id', $church->region_id);
                                $set('district_id', $church->district_id);
                                $set('sector_id', $church->sector_id);
                                $set('state_id', $church->state_id);
                                $set('city_id', $church->city_id);
                                $set('municipality_id', $church->municipality_id);
                                $set('parish_id', $church->parish_id);
                                $set('address', $church->address);
                            } else {
                                // 🧹 Limpiar los campos relacionados si se deselecciona la iglesia
                                $set('code_church', null);
                                $set('region_id', null);
                                $set('district_id', null);
                                $set('sector_id', null);
                                $set('state_id', null);
                                $set('city_id', null);
                                $set('municipality_id', null);
                                $set('parish_id', null);
                                $set('address', null);
                            }
                        })
                        ->disabled(function () {
                            // 🔹 Deshabilitar el campo si el usuario no tiene los roles permitidos
                            return !Auth::user()->hasAnyRole([
                                'Administrador',
                                'Secretario Nacional',
                                'Tesorero Nacional',
                                'Secretario Regional',
                                'Secretario Sectorial', 
                            ]);
                        })
                        ->dehydrated(),
                
                





                Forms\Components\TextInput::make('code_church')
                    ->label('Código de la Iglesia')
                    ->placeholder('Código de la iglesia')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Select::make('region_id')
                    ->label('Región')
                    ->relationship('region', 'name')
                    ->placeholder('Selecciona una región')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Select::make('district_id')
                    ->label('Distrito')
                    ->relationship('district', 'name')
                    ->placeholder('Selecciona un distrito')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Select::make('sector_id')
                    ->label('Sector')
                    ->relationship('sector', 'name')
                    ->placeholder('Selecciona un sector')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Select::make('state_id')
                    ->label('Estado')
                    ->relationship('state', 'name')
                    ->placeholder('Selecciona un estado')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Select::make('city_id')
                    ->label('Ciudad')
                    ->relationship('city', 'name')
                    ->placeholder('Selecciona una ciudad')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Select::make('municipality_id')
                    ->label('Municipio')
                    ->relationship('municipality', 'name')
                    ->placeholder('Selecciona un municipio')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Select::make('parish_id')
                    ->label('Parroquia')
                    ->relationship('parish', 'name')
                    ->placeholder('Selecciona una parroquia')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Textarea::make('address')
                    ->label('Dirección')
                    ->rows(3)
                    ->placeholder('Dirección de la iglesia seleccionada')
                    ->disabled() // Campo visible pero deshabilitado
                    ->dehydrated(), // Se encarga de enviar los datos al database

                Forms\Components\Toggle::make('abisop')
                    ->label('¿Cancela Abisop?')
                    ->default(false)
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

                Forms\Components\Toggle::make('iblc')
                    ->label('¿Es egresado del IBLC?')
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

                Forms\Components\Select::make('course_type_id')
                    ->label('Tipo de Curso')
                    ->relationship('courseType', 'name')
                    ->placeholder('Seleccione un tipo de curso')
                    ->native(false)
                    ->visible(fn (callable $get) => $get('iblc'))
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

                Forms\Components\TextInput::make('promotion_year')
                    ->label('Año de Promoción')
                    ->numeric()
                    ->placeholder('Año en formato YYYY')
                    ->visible(fn (callable $get) => $get('iblc'))
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

                Forms\Components\TextInput::make('promotion_number')
                    ->label('Número de Promoción')
                    ->maxLength(255)
                    ->placeholder('Número de promoción')
                    ->visible(fn (callable $get) => $get('iblc'))
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


                
                Forms\Components\Select::make('position_type_id')
                    ->label('Tipo de Cargo')
                    ->relationship('positionType', 'name') // Relación con el modelo PositionType
                    ->required()
                    ->reactive() // Marca el campo como reactivo
                    ->native(false)
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Verificar si el tipo de cargo seleccionado es "No Aplica"
                        if ($state == 5) { // Suponiendo que el ID para "No Aplica" es 0
                            $set('current_position_id', null); // Limpiar el segundo select
                            $set('disable_current_position', true); // Deshabilitar el segundo select
                        } else {
                            $set('disable_current_position', false); // Habilitar el segundo select
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
                    //->columnSpan(['default' => 3, 'md' => 1]),

                Forms\Components\Select::make('current_position_id')
                    ->label('Cargo Actual')
                    ->searchable()
                    ->options(function (callable $get) {
                        $positionTypeId = $get('position_type_id');
                        if ($positionTypeId && $positionTypeId != 5) { 
                            return \App\Models\CurrentPosition::where('position_type_id', $positionTypeId)
                                ->pluck('name', 'id');
                        }
                        return [];
                    })
                    ->placeholder('Selecciona una posición')
                    ->disabled(fn (callable $get) => $get('disable_current_position') ?? false)
                    ->native(false)
                    ->reactive() // 🔹 Para recalcular el nivel en tiempo real
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        // Obtenemos el Pastor padre (ownerRecord) y su fecha de inicio
                        $pastor = $this->ownerRecord;
                        if (! $pastor->start_date_ministry) {
                            return; 
                        }
                
                        // Calculamos el nuevo nivel con base en la fecha de ministerio y la nueva posición
                        $levelId = \App\Services\PastorAssignmentService::determineLevel(
                            $pastor->start_date_ministry,
                            $get('current_position_id')
                        );
                
                        // Asignamos el nuevo nivel al campo 'pastor_level_id'
                        $set('pastor_level_id', $levelId);
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
                
                    //->columnSpan(['default' => 3, 'md' => 1]),



                    
                    Forms\Components\Select::make('pastor_licence_id')
                        ->label('Licencia Pastoral')
                        ->relationship('pastorLicence', 'name')
                        ->native(false)
                        ->default(function ($livewire) {
                            // "ownerRecord" es el Pastor asociado al Relation Manager
                            $pastor = $livewire->ownerRecord;
                    
                            // Si el usuario es Admin o Secretario, permitimos que sobreescriba manualmente
                            if (Auth::user()->hasAnyRole(['Administrador', 'Secretario Nacional'])) {
                                // Si en la URL (request) viene un pastor_licence_id, lo usamos
                                if (request('pastor_licence_id')) {
                                    return request('pastor_licence_id');
                                }
                            }
                    
                            // Si no, calculamos automáticamente usando PastorAssignmentService
                            return \App\Services\PastorAssignmentService::determineLicence(
                                $pastor->pastor_income_id,
                                $pastor->pastor_type_id,
                                $pastor->start_date_ministry
                            );
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                            // Si la licencia ingresada no es un ID numérico, volvemos a forzar el cálculo
                            if (! is_numeric($state)) {
                                $pastor = $livewire->ownerRecord;
                    
                                $licenceId = \App\Services\PastorAssignmentService::determineLicence(
                                    $pastor->pastor_income_id,
                                    $pastor->pastor_type_id,
                                    $pastor->start_date_ministry
                                );
                    
                                // Sobrescribimos con la licencia calculada
                                $set('pastor_licence_id', $licenceId);
                            }
                        })
                        ->disabled(fn () => ! Auth::user()->hasAnyRole(['Administrador','Secretario Nacional']))
                        ->dehydrated(), 
                
                
                
                
                



                
                
                

                    


                    Forms\Components\Toggle::make('appointment')
                        ->label('¿Nombramiento?')
                        ->default(false)
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

                    Forms\Components\Select::make('pastor_level_id')
                        ->label('Nivel Pastoral')
                        ->relationship('pastorLevel', 'name')
                        ->placeholder('Selecciona un nivel')
                        ->default(function ($livewire) {
                            // "ownerRecord" es el Pastor padre
                            $pastor = $livewire->ownerRecord;
                    
                            // Si el usuario es Admin o Secretario, podemos respetar un valor manual del request
                            if (Auth::user()->hasAnyRole(['Administrador', 'Secretario Nacional'])) {
                                if (request('pastor_level_id')) {
                                    return request('pastor_level_id');
                                }
                            }
                    
                            // Caso contrario, calculamos automáticamente con PastorAssignmentService
                            return \App\Services\PastorAssignmentService::determineLevel(
                                $pastor->start_date_ministry,
                                $pastor->current_position_id
                            );
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                            // Si el valor no es numérico, forzamos el cálculo
                            if (! is_numeric($state)) {
                                $pastor = $livewire->ownerRecord;
                    
                                $levelId = \App\Services\PastorAssignmentService::determineLevel(
                                    $pastor->start_date_ministry,
                                    $pastor->current_position_id
                                );
                    
                                $set('pastor_level_id', $levelId);
                            }
                        })
                        ->native(false)
                        ->disabled(function () {
                            // Sólo Admin/Secretario pueden editar
                            return !Auth::user()->hasAnyRole([
                                'Administrador',
                                'Secretario Nacional',
                            ]);
                        })
                        ->dehydrated(),
                    
                        
                    
                    
                    
                    
                    //Forms\Components\Select::make('pastor_level_vip_id')
                        //->label('Nivel Pastoral VIP')
                        //->relationship('pastorLevelVip', 'name') // Relación con la tabla PastorLevelVip
                        //->placeholder('Seleccione un nivel VIP'),


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Description')
            ->columns([
                Tables\Columns\TextColumn::make('code_pastor')
                    ->label('Código del Pastor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pastor.start_date_ministry') // Accede a la relación pastor y su campo start_date_ministry
                    ->label('Inicio Ministerio')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pastorIncome.name')
                    ->label('Ingreso Pastoral')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pastorType.name')
                    ->label('Tipo de Pastor')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BooleanColumn::make('active')
                    ->label('Activo')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('church.name')
                    ->label('Iglesia Asociada')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('code_church')
                    ->label('Código de la Iglesia')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('region.name')
                    ->label('Región')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('district.name')
                    ->label('Distrito')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sector.name')
                    ->label('Sector')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BooleanColumn::make('abisop')
                    ->label('ABISOP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BooleanColumn::make('iblc')
                    ->label('IBLC')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('courseType.name')
                    ->label('Tipo de Curso')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pastorLicence.name')
                    ->label('Licencia Pastoral')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pastorLevel.name')
                    ->label('Nivel Pastoral')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('positionType.name')
                    ->label('Tipo de Posición')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('currentPosition.name')
                    ->label('Posición Actual')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BooleanColumn::make('appointment')
                    ->label('Nombramiento')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('promotion_year')
                    ->label('Año de Promoción')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('promotion_number')
                    ->label('Número de Promoción')
                    ->sortable()
                    ->toggleable()
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Información')
                    ->modalHeading('Nueva Información Ministerial')
                    ->hidden(fn () => $this->getTableQuery()->exists()), // Oculta si ya existe un registro
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Habilita la opción de "Ver"
                Tables\Actions\EditAction::make(), // Habilita la opción de "Editar"
                Tables\Actions\Action::make('detach')
                    ->label('Desvincular Iglesia')
                    ->requiresConfirmation() // Solicitar confirmación antes de ejecutar la acción
                    ->modalHeading('Desvincular Pastor')
                    ->modalDescription('¿Estás seguro de que deseas desvincular esta iglesia del pastor?')
                    ->modalSubmitActionLabel('Sí, desvincular')
                    ->action(function ($record) {
                        if (!$record) {
                            throw new \Exception('El registro no existe.');
                        }
            
                        // Actualizar el campo church_id a null en la tabla pastor_ministries
                        $record->update(['church_id' => null]);
            
                        // Mostrar una notificación de éxito
                        Notification::make()
                            ->title('Éxito')
                            ->body('El pastor ha sido desvinculado correctamente.')
                            ->success()
                            ->send();
                    })
                    ->disabled(fn () => !Auth::user()->hasAnyRole([
                        'Administrador',
                        'Secretario Nacional',
                        'Tesorero Nacional',
                        'Secretario Regional',
                        'Secretario Sectorial',
                    ])) // Deshabilitar si el usuario no tiene los roles permitidos
                    ->tooltip(fn () => Auth::user()->hasAnyRole([
                        'Administrador',
                        'Secretario Nacional',
                        'Tesorero Nacional',
                        'Secretario Regional',
                        'Secretario Sectorial',
                    ]) ? null : 'No tienes permiso para realizar esta acción'), // Tooltip informativo
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    
}