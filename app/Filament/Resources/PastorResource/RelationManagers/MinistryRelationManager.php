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
                        ->reactive() // 🔹 Permite que al cambiar este campo, otros valores dependientes se recalculen
                        ->afterStateUpdated(fn (callable $set, callable $get) => 
                            $set('pastor_licence_id', \App\Services\PastorLicenceService::determineLicence(
                                $get('pastor_income_id'), 
                                $get('pastor_type_id'),
                                $get('start_date_ministry')
                            ))
                        ) // 🔹 Actualiza automáticamente la licencia
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
                        ->reactive() // 🔹 Permite que al cambiar este campo, la licencia también se recalcule
                        ->afterStateUpdated(fn (callable $set, callable $get) => 
                            $set('pastor_licence_id', \App\Services\PastorLicenceService::determineLicence(
                                $get('pastor_income_id'), 
                                $get('pastor_type_id'),
                                $get('start_date_ministry')
                            ))
                        ) // 🔹 Se recalcula automáticamente la licencia pastoral
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
                                $set('address', $church->address);
                            } else {
                                // 🧹 Limpiar los campos relacionados si se deselecciona la iglesia
                                $set('code_church', null);
                                $set('region_id', null);
                                $set('district_id', null);
                                $set('sector_id', null);
                                $set('state_id', null);
                                $set('city_id', null);
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
                        if ($positionTypeId && $positionTypeId != 5) { // Suponiendo que el ID para "No Aplica" es 0
                            return \App\Models\CurrentPosition::where('position_type_id', $positionTypeId)
                                ->pluck('name', 'id');
                        }
                        return [];
                    })
                    //->required()
                    ->placeholder('Selecciona una posición')
                    ->disabled(fn (callable $get) => $get('disable_current_position') ?? false) // Deshabilita si está configurado
                    ->native(false)
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
                        ->placeholder('Selecciona una licencia')
                        ->default(function (callable $get) {
                            return PastorLicenceService::determineLicence(
                                $get('pastor_income_id'),
                                $get('pastor_type_id'),
                                $get('start_date_ministry')
                            );
                        })
                        ->reactive()
                        ->native(false)
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            // ✅ Solo recalcula si no se ha editado manualmente
                            if (!is_numeric($get('pastor_licence_id'))) {
                                $set('pastor_licence_id', PastorLicenceService::determineLicence(
                                    $get('pastor_income_id'),
                                    $get('pastor_type_id'),
                                    $get('start_date_ministry')
                                ));
                            }
                        })
                        ->disabled(fn () => !Auth::user()->hasAnyRole(['Administrador', 'Secretario Nacional']))
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
                        ->label('Nivel Pastoral por Fecha')
                        ->relationship('pastorLevel', 'name')
                        ->placeholder('Selecciona un nivel')
                        ->default(function (callable $get) {
                            // Obtener la fecha de inicio del ministerio
                            $startDate = $get('start_date_ministry');
                            $currentPositionId = $get('current_position_id');
                    
                            if ($startDate) {
                                // Calcular los años de ministerio
                                $startDate = Carbon::parse($startDate)->startOfDay();
                                $today = now()->startOfDay();
                                $yearsInMinistry = $startDate->diffInYears($today);
                    
                                // Asignar nivel basado en la posición actual (current_position_id)
                                if ($currentPositionId == 17) {
                                    return \App\Models\PastorLevel::where('name', 'PLATINO PLUS')->value('id');
                                } elseif (in_array($currentPositionId, [2, 14, 15])) {
                                    return \App\Models\PastorLevel::where('name', 'DIAMANTE')->value('id');
                                } elseif ($currentPositionId == 1) {
                                    return \App\Models\PastorLevel::where('name', 'ZAFIRO')->value('id');
                                }
                    
                                // Asignar nivel basado en los años de ministerio
                                if ($yearsInMinistry <= 6) {
                                    return \App\Models\PastorLevel::where('name', 'BRONCE')->value('id');
                                } elseif ($yearsInMinistry >= 7 && $yearsInMinistry <= 12) {
                                    return \App\Models\PastorLevel::where('name', 'PLATA')->value('id');
                                } elseif ($yearsInMinistry >= 13 && $yearsInMinistry <= 20) {
                                    return \App\Models\PastorLevel::where('name', 'TITANIO')->value('id');
                                } elseif ($yearsInMinistry >= 21 && $yearsInMinistry <= 35) {
                                    return \App\Models\PastorLevel::where('name', 'ORO')->value('id');
                                } elseif ($yearsInMinistry >= 36) {
                                    return \App\Models\PastorLevel::where('name', 'PLATINO')->value('id');
                                }
                            }
                    
                            return null; // Ningún nivel asignado
                        })
                        ->reactive()
                        ->native(false)
                        ->disabled(function () {
                            // Deshabilitar el campo si el usuario no tiene los roles permitidos
                            return !Auth::user()->hasAnyRole([
                                'Administrador',
                                'Secretario Nacional',
                                'Tesorero Nacional',
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