<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PastorResource\Pages;
use App\Filament\Resources\PastorResource\RelationManagers;
use App\Filament\Resources\PastorResource\RelationManagers\FamilyRelationManager;
use App\Filament\Resources\PastorResource\RelationManagers\MinistryRelationManager;
use App\Models\Region;
use App\Models\District;
use App\Models\Sector;
use App\Services\CarnetService;
use App\Services\HojaDeVidaService;
use App\Services\NombramientoService;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Models\Pastor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\AccessControlTrait;
use App\Traits\Filters\HasUbicacionGeograficaFilters;
use App\Services\TerritorialFormService;
use Filament\Tables\Actions\LinkAction;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Filters\UbicacionFilters;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PastorResource extends Resource
{
    use AccessControlTrait;
    use HasUbicacionGeograficaFilters;
    
    protected static ?string $model = Pastor::class;

    protected static ?int $navigationSort = 2; // Orden

    public static function getPluralModelLabel(): string
    {
        return 'Pastores'; // Texto personalizado para el título principal
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

    protected static ?string $navigationIcon = 'heroicon-m-user-group';

    public static function getSearchable(): array
    {
        return [
            'name',
            'lastname',
            'number_cedula',
            'email',
        ];
    }

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


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(4) // Dividimos el formulario en 4 partes proporcionales
                    ->schema([
                        // Información Básica ocupa 3/4 (75%)
                        Forms\Components\Section::make('Datos Personales')
                            ->schema([
                                Forms\Components\Grid::make(2) // Campos distribuidos en 2 columnas
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(45),
                                            
                                            
                                        Forms\Components\TextInput::make('lastname')
                                            ->label('Apellido')
                                            ->required()
                                            ->maxLength(45),
                                            
                                            
                                        Forms\Components\Select::make('nationality_id')
                                            ->label('Nacionalidad')
                                            ->relationship('nationality', 'name')
                                            ->required()
                                            ->disabled(function ($livewire) {
                                                // Permitir edición en la creación
                                                if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                                                    return false;
                                                }
                                        
                                                // En la edición, restringir según roles
                                                if ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                                                    return !auth()->user()->hasAnyRole([
                                                        'Administrador',
                                                        'Secretario Nacional',
                                                        'Tesorero Nacional',
                                                        'Secretario Regional',
                                                        'Supervisor Distrital',
                                                    ]);
                                                }
                                        
                                                // Otros casos (si los hay), puedes decidir aquí
                                                return false; // Por defecto, habilitado si no es ni creación ni edición
                                            })
                                            ->dehydrated(),
                                            
                                        Forms\Components\TextInput::make('number_cedula')
                                            ->label('Número de Cédula')
                                            ->required()
                                            ->maxLength(8)
                                            ->disabled(function ($livewire) {
                                                // Permitir edición en la creación
                                                if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                                                    return false;
                                                }
                                        
                                                // En la edición, restringir según roles
                                                if ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                                                    return !auth()->user()->hasAnyRole([
                                                        'Administrador',
                                                        'Secretario Nacional',
                                                        'Tesorero Nacional',
                                                        'Secretario Regional',
                                                        'Supervisor Distrital',
                                                    ]);
                                                }
                                        
                                                // Otros casos (si los hay), puedes decidir aquí
                                                return false; // Por defecto, habilitado si no es ni creación ni edición
                                            })
                                            ->dehydrated(),
                                        
                                        
                                    ]),

                            ])
                            ->columnSpan(3), // Ocupa 3 columnas del grid principal

                        // Foto de Perfil ocupa 1/4 (25%)
                        Forms\Components\Section::make('Foto del Pastor')
                            ->schema([
                                Forms\Components\FileUpload::make('photo_pastor')
                                    ->label('Foto del Pastor')
                                    ->directory('pastors_photos') // Carpeta donde se almacenará el archivo
                                    ->image() // Acepta solo imágenes
                                    ->maxSize(2048) // Tamaño máximo en KB
                                    ->nullable(), // Permite que sea opcional

                            ])
                            ->columnSpan(1), // Ocupa 1 columna del grid principal
                    ])
                    ->columnSpan(3),

                // Información básica
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\Grid::make(3) // Tres columnas
                        ->schema([
                            Forms\Components\DatePicker::make('birthdate')
                                ->label('Fecha de Nacimiento')
                                ->required()
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\TextInput::make('birthplace')
                                ->label('Lugar de Nacimiento')
                                ->required()
                                ->maxLength(55)
                                ->columnSpan(['default' => 3, 'md' => 2]),

                            Forms\Components\Select::make('gender_id')
                                ->label('Género')
                                ->relationship('gender', 'name')
                                ->required()
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\Select::make('marital_status_id')
                                ->label('Estado Civil')
                                ->relationship('maritalStatus', 'name')
                                ->required()
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\Select::make('blood_type_id')
                                ->label('Tipo de Sangre')
                                ->relationship('bloodType', 'name')
                                ->required()
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\DatePicker::make('baptism_date')
                                ->label('Fecha de Bautismo')
                                ->required()
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\TextInput::make('who_baptized')
                                ->label('Quién le Bautizó')
                                ->required()
                                ->maxLength(55)
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\DatePicker::make('start_date_ministry')
                                ->label('Fecha de Inicio del Ministerio')
                                ->required()
                                ->columnSpan(['default' => 3, 'md' => 1])
                                ->disabled(function ($livewire) {
                                    // Permitir edición en la creación
                                    if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                                        return false;
                                    }
                            
                                    // En la edición, restringir según roles
                                    if ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                                        return !auth()->user()->hasAnyRole([
                                            'Administrador',
                                            'Secretario Nacional',
                                            'Tesorero Nacional',
                                        ]);
                                    }
                            
                                    // Otros casos (si los hay), puedes decidir aquí
                                    return false; // Por defecto, habilitado si no es ni creación ni edición
                                })
                                ->dehydrated(),


                        ]),

                    ])
                    ->columnSpan(3),

                // Información básica
                Forms\Components\Section::make('Dirección de Residencia')
                    ->schema([
                        Forms\Components\Grid::make(3) // Tres columnas
                            ->schema([
                                ...TerritorialFormService::getTerritorialComponents(),
                            ])
                            ->columns(['default' => 1, 'md' => 3]),

                        Forms\Components\Grid::make(3) // Dos columnas
                            ->schema([
                                Forms\Components\Select::make('state_id')
                                    ->label('Estado')
                                    ->native(false)
                                    ->options(fn () => \App\Models\State::pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Seleccione un estado...')
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(fn ($state, Set $set) => [
                                        $set('city_id', null),
                                        $set('municipality_id', null),
                                        $set('parish_id', null),
                                    ])
                                    ->columnSpan(['default' => 3, 'md' => 1]),

                                Forms\Components\Select::make('city_id')
                                    ->label('Ciudad')
                                    ->native(false)
                                    ->options(fn (Get $get) => \App\Models\City::where('state_id', $get('state_id'))->pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Seleccione una ciudad...')
                                    ->reactive()
                                    ->required()
                                    ->disabled(fn (Get $get) => !$get('state_id'))
                                    ->afterStateUpdated(fn ($state, Set $set) => [
                                        $set('municipality_id', null),
                                        $set('parish_id', null),
                                    ])
                                    ->columnSpan(['default' => 3, 'md' => 1]),

                                Forms\Components\Select::make('municipality_id')
                                    ->label('Municipio')
                                    ->native(false)
                                    ->options(fn (Get $get) => \App\Models\Municipality::where('state_id', $get('state_id'))->pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Seleccione un municipio...')
                                    ->reactive()
                                    ->required()
                                    ->disabled(fn (Get $get) => !$get('city_id'))
                                    ->afterStateUpdated(fn ($state, Set $set) => $set('parish_id', null))
                                    ->columnSpan(['default' => 3, 'md' => 1]),
                                
                                Forms\Components\Select::make('parish_id')
                                    ->label('Parroquia')
                                    ->native(false)
                                    ->options(fn (Get $get) => \App\Models\Parish::where('municipality_id', $get('municipality_id'))->pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Seleccione una parroquia...')
                                    ->reactive()
                                    ->required()
                                    ->disabled(fn (Get $get) => !$get('municipality_id'))
                                    ->columnSpan(['default' => 3, 'md' => 1]),

                                Forms\Components\Select::make('housing_type_id')
                                    ->label('Tipo de Vivienda')
                                    ->relationship('housingType', 'name')
                                    ->required()
                                    ->columnSpan(['default' => 3, 'md' => 1]),

                                Forms\Components\Textarea::make('address')
                                    ->label('Dirección')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(3),

                // Información de contacto
                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone_mobile')
                                    ->label('Teléfono Móvil')
                                    ->tel()
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 2, 'md' => 1]),

                                Forms\Components\TextInput::make('phone_house')
                                    ->label('Teléfono de Casa')
                                    ->tel()
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 2, 'md' => 1]),

                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->required()
                                    ->unique(
                                        column: 'email', // Especificamos la columna directamente
                                        ignoreRecord: true // Ignoramos el registro actual para evitar conflictos al editar
                                    )
                                    ->maxLength(255), 

                            ])
                            ->columns([
                                'sm' => 1,
                                'lg' => 3,
                            ]),

                    ])
                    ->columnSpan(3),



                // Datos académicos
                Forms\Components\Section::make('Datos Académicos')
                    ->schema([
                        Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('academic_level_id')
                                ->label('Nivel Académico')
                                ->relationship('academicLevel', 'name')
                                ->required()
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\TextInput::make('career')
                                ->label('Carrera')
                                ->maxLength(85)
                                ->columnSpan(['default' => 3, 'md' => 2]),

                            Forms\Components\TextInput::make('other_studies')
                                ->label('Otros Estudios')
                                ->maxLength(85)
                                ->columnSpan(['default' => 3, 'md' => 2]),

                            Forms\Components\Toggle::make('other_work')
                                ->label('¿Tiene Otro Trabajo?')
                                ->required()
                                ->columnSpan(['default' => 3, 'md' => 1])
                                ->reactive(),

                            Forms\Components\TextInput::make('how_work')
                                ->label('Forma de Trabajo')
                                ->maxLength(85)
                                ->visible(fn (callable $get) => $get('other_work'))
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\Toggle::make('social_security')
                                ->label('¿Tiene Seguro Social?')
                                ->columnSpan(['default' => 3, 'md' => 1]),

                            Forms\Components\Toggle::make('housing_policy')
                                ->label('¿Tiene Política Habitacional?')
                                ->columnSpan(['default' => 3, 'md' => 1]),

                        ])
                    ])
                    ->columnSpan(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_pastor')
                    ->label('Foto')
                    ->rounded(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lastname')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),

                // ID Personal
                Tables\Columns\TextColumn::make('number_cedula')
                    ->label('Cédula')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('pastorMinistry.code_pastor')
                    ->label('Código del Pastor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nationality.name')
                    ->label('Nacionalidad')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('start_date_ministry')
                    ->label('Inicio del Ministerio')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('gender.name')
                    ->label('Género')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bloodType.name')
                    ->label('Tipo de Sangre')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('academicLevel.name')
                    ->label('Nivel Académico')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('career')
                    ->label('Profesión')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone_mobile')
                    ->label('Teléfono Móvil')
                    ->searchable(),
                    //->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone_house')
                    ->label('Teléfono Casa')
                    ->searchable(),
                    //->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('birthplace')
                    ->label('Lugar de Nacimiento')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('who_baptized')
                    ->label('Quién Bautizó')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('how_work')
                    ->label('Ocupación')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('other_studies')
                    ->label('Otros Estudios')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('birthdate')
                    ->label('Fecha de Nacimiento')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('baptism_date')
                    ->label('Fecha de Bautismo')
                    ->date()
                    ->sortable()
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

                Tables\Columns\TextColumn::make('municipality.name')
                    ->label('Municipio')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('parish.name')
                    ->label('Parroquia')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('social_security')
                    ->label('¿Seguro Social?')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('housing_policy')
                    ->label('¿Política Habitacional?')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('other_work')
                    ->label('¿Otra Ocupación?')
                    ->boolean()
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


            
            
                Tables\Filters\SelectFilter::make('academic_level_id')
                    ->label('Nivel Académico')
                    ->relationship('academicLevel', 'name')
                    ->placeholder('Todos los niveles académicos'),
            
                //Tables\Filters\Filter::make('has_social_security')
                    //->label('Con Seguro Social')
                    //->query(fn (Builder $query): Builder => $query->where('social_security', true)),
            
                //Tables\Filters\Filter::make('has_housing_policy')
                    //->query(fn (Builder $query): Builder => $query->where('housing_policy', true)),
            
                //Tables\Filters\Filter::make('active_ministers')
                    //->label('Ministros Activos')
                    //->query(fn (Builder $query): Builder => $query->where('active', true)),
            
                Tables\Filters\Filter::make('start_date_ministry')
                    ->label('Inicio en el Ministerio')
                    ->form([
                        Forms\Components\DatePicker::make('start_date_min')
                            ->label('Fecha Mínima'),
                        Forms\Components\DatePicker::make('start_date_max')
                            ->label('Fecha Máxima'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['start_date_min'], fn (Builder $query, $date) => $query->whereDate('start_date_ministry', '>=', $date))
                            ->when($data['start_date_max'], fn (Builder $query, $date) => $query->whereDate('start_date_ministry', '<=', $date));
                    }),
            ])
            
            ->actions([
                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->label(false)
                    ->tooltip('Ver Registro')
                    ->color('primary')
                    ->size('md')
                    ->modalHeading(fn ($record) => 'Detalles de: ' . $record->name),

                EditAction::make()
                    ->icon('heroicon-s-pencil-square')    
                    ->label(false)
                    ->tooltip('Editar Pastor')
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
                    
                Action::make('descargarHojaDeVida')
                    ->label(false)
                    ->tooltip('Hoja de Vida')
                    ->color('primary')
                    ->size('md')
                    ->icon('heroicon-o-document')
                    ->action(function (Pastor $record) {
                        $service = new HojaDeVidaService();
                
                        // Usar el método fillHojaDeVida(...) que sí existe
                        $pdfPath = $service->fillHojaDeVida($record);
                
                        $cedula = $record->number_cedula ?? 'SIN_CEDULA';
                        return response()->download($pdfPath, "hoja_de_vida_{$cedula}.pdf");
                    }),
                
                        
                Action::make('downloadDocuments')
                    ->icon('heroicon-o-document-arrow-down')
                    ->label(false)
                    ->tooltip('Documentos del Pastor')
                    ->color('success')
                    ->size('md')
                    ->modalHeading('Descargar Documentos del Pastor')
                    ->modalSubheading('Esto generará la Hoja de Vida, Nombramiento y Licencia (ambas caras).')
                    ->modalButton('Generar y Descargar')
                    ->requiresConfirmation()
                    ->action(function (Pastor $record) {
                        $cedula = $record->number_cedula;

                        // Servicios
                        $carnetService       = app(CarnetService::class);
                        $hojaDeVidaService   = app(HojaDeVidaService::class);
                        $nombramientoService = app(NombramientoService::class);

                        // 1. Generar documentos
                        $carnetService->generateCarnet($record);
                        $hojaPath  = $hojaDeVidaService->fillHojaDeVida($record);
                        $nombrPath = $nombramientoService->fillNombramiento($record);

                        // 2. Rutas físicas de los documentos
                        $front = storage_path("app/public/carnets/{$cedula}_carnet_front.png");
                        $back  = storage_path("app/public/carnets/{$cedula}_carnet_back.png");
                        $hoja  = $hojaPath;
                        $nombr = $nombrPath;

                        // 3. Verificar existencia
                        $archivosFaltantes = [];
                        foreach ([
                            'Carnet Frontal' => $front,
                            'Carnet Posterior' => $back,
                            'Hoja de Vida' => $hoja,
                            'Nombramiento' => $nombr,
                        ] as $nombre => $ruta) {
                            if (!file_exists($ruta)) {
                                $archivosFaltantes[] = "$nombre no se generó: $ruta";
                            }
                        }

                        if (count($archivosFaltantes)) {
                            Log::error('Faltan documentos del pastor', ['errores' => $archivosFaltantes]);
                            throw new \Exception("No se pudieron generar todos los documentos:\n" . implode("\n", $archivosFaltantes));
                        }

                        // 4. Crear directorio de documentos si no existe
                        Storage::disk('public')->makeDirectory('documentos');

                        // 5. Crear archivo ZIP
                        $zipName = "documentos_pastor_{$cedula}.zip";
                        $zipPath = storage_path("app/public/documentos/{$zipName}");

                        $zip = new ZipArchive();
                        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                            throw new \Exception("No se pudo crear el archivo ZIP en: $zipPath");
                        }

                        $zip->addFile($front, "carnet_front_{$cedula}.png");
                        $zip->addFile($back,  "carnet_back_{$cedula}.png");
                        $zip->addFile($hoja,  "hoja_de_vida_{$cedula}.pdf");
                        $zip->addFile($nombr, "nombramiento_{$cedula}.pdf");
                        $zip->close();

                        // 6. Descargar archivo ZIP
                        return response()->download($zipPath, $zipName)->deleteFileAfterSend(false);
                    })
                    ->visible(fn () => auth()->user()?->hasAnyRole([
                        'Obispo Presidente',
                        'Secretario Nacional',
                        'Tesorero Nacional',
                        'Administrador',
                    ])),



            ])
            ->recordUrl(fn ($record) => auth()->user()?->hasAnyRole([
                'Administrador',
                'Secretario Nacional',
                'Tesorero Nacional',
                'Secretario Regional',
                'Supervisor Distrital',
                'Secretario Sectorial',
            ]) ? PastorResource::getUrl('edit', ['record' => $record]) : null) // Solo los autorizados pueden abrir la edición al hacer clic
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar Pastores Seleccionados')
                        ->visible(function () {
                            $user = auth()->user();
                            return $user?->hasAnyRole([
                                'Administrador',
                                'Tesorero Nacional',
                            ]);
                        }),
                ]),
            ])
            //->emptyStateIcon('heroicon-o-user') // Icono para el estado vacío
            ->emptyStateHeading('Aun no has sido registrado como pastor.')
            ->emptyStateDescription('Contacte al Secretario de su Sector para que asocie tu usuario con su registro pastoral.')
            ->emptyStateActions([
                //
            ]);
    }

    


    public static function getRelations(): array
    {
        return [
            FamilyRelationManager::class,
            MinistryRelationManager::class,
        ];
    }


    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['church_id']) && isset($data['pastor_type_id'])) {
            $church = Church::find($data['church_id']);
            if ($church) {
                // Verificar el límite de pastores según el tipo
                $count = $church->ministries()
                    ->where('pastor_type_id', $data['pastor_type_id'])
                    ->count();

                if ($data['pastor_type_id'] == 1 && $count >= 1) {
                    throw new \Exception('Esta iglesia ya tiene un pastor Titular asignado.');
                }
                if ($data['pastor_type_id'] == 2 && $count >= 1) {
                    throw new \Exception('Esta iglesia ya tiene un pastor Adjunto asignado.');
                }
                if ($data['pastor_type_id'] == 3 && $count >= 2) {
                    throw new \Exception('Esta iglesia ya tiene el máximo de pastores Asistentes asignados.');
                }
            }
        }

        return $data;
    }

    public static function afterSave(Pastor $record, array $data): void
    {
        if (isset($data['church_id']) && isset($data['pastor_type_id'])) {
            // Crear o actualizar la relación en pastor_ministries
            $record->ministries()->updateOrCreate(
                ['pastor_id' => $record->id], // Clave única
                [
                    'church_id' => $data['church_id'],
                    'pastor_type_id' => $data['pastor_type_id'],
                ]
            );
        } else {
            // Si no hay iglesia seleccionada, desvincular al pastor
            $record->ministries()->update(['church_id' => null]);
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPastors::route('/'),
            'create' => Pages\CreatePastor::route('/create'),
            'edit' => Pages\EditPastor::route('/{record}/edit'),
        ];
    }
}