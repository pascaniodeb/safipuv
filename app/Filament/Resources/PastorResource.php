<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PastorResource\Pages;
use App\Filament\Resources\PastorResource\RelationManagers;
use App\Filament\Resources\PastorResource\RelationManagers\FamilyRelationManager;
use App\Filament\Resources\PastorResource\RelationManagers\MinistryRelationManager;
use App\Services\CarnetService;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Models\Pastor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\AccessControlTrait;
use Filament\Tables;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PastorResource extends Resource
{
    use AccessControlTrait;
    
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

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole([
            'Secretario Nacional',
            'Secretario Regional',
            'Secretario Sectorial',
        ]);
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
                                        Forms\Components\TextInput::make('lastname')
                                            ->label('Apellido')
                                            ->required()
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
                                        Forms\Components\Select::make('nationality_id')
                                            ->label('Nacionalidad')
                                            ->relationship('nationality', 'name')
                                            ->required()
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
                                        Forms\Components\TextInput::make('number_cedula')
                                            ->label('Número de Cédula')
                                            ->required()
                                            ->maxLength(255)
                                            ->disabled(function () {
                                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                                return !Auth::user()->hasAnyRole([
                                                    'Administrador',
                                                    'Secretario Nacional',
                                                    'Tesorero Nacional', 
                                                ]);
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
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Administrador',
                                        'Secretario Nacional',
                                        'Tesorero Nacional',
                                    ]);
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
                                Forms\Components\Select::make('region_id')
                                    ->label('Región')
                                    ->relationship('region', 'name')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set) => $set('district_id', null))
                                    ->columnSpan(['default' => 3, 'md' => 1])
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
                                    ->afterStateUpdated(fn (callable $set) => $set('sector_id', null))
                                    ->disabled(fn (callable $get) => !$get('region_id'))
                                    ->columnSpan(['default' => 3, 'md' => 1])
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
                                    ->disabled(fn (callable $get) => !$get('district_id'))
                                    ->columnSpan(['default' => 3, 'md' => 1])
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

                            ]),

                        Forms\Components\Grid::make(3) // Dos columnas
                            ->schema([
                                Forms\Components\Select::make('state_id')
                                    ->label('Estado')
                                    ->relationship('state', 'name')
                                    ->required()
                                    ->reactive() // Marca el campo como reactivo
                                    ->afterStateUpdated(fn (callable $set) => $set('city_id', null))
                                    ->columnSpan(['default' => 3, 'md' => 1]),

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
                                    ->disabled(fn (callable $get) => !$get('state_id'))
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
                                ->label('¿Tiene Seguro Social?'),

                            Forms\Components\Toggle::make('housing_policy')
                                ->label('¿Tiene Política Habitacional?'),

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
                Tables\Filters\SelectFilter::make('region_id')
                    ->label('Región')
                    ->relationship('region', 'name')
                    ->placeholder('Todas las regiones'),

                Tables\Filters\SelectFilter::make('gender_id')
                    ->label('Género')
                    ->relationship('gender', 'name')
                    ->placeholder('Todos los géneros'),

                Tables\Filters\SelectFilter::make('academic_level_id')
                    ->label('Nivel Académico')
                    ->relationship('academicLevel', 'name')
                    ->placeholder('Todos los niveles académicos'),

                Tables\Filters\SelectFilter::make('marital_status_id')
                    ->label('Estado Civil')
                    ->relationship('maritalStatus', 'name')
                    ->placeholder('Todos los estados civiles'),

                Tables\Filters\Filter::make('has_social_security')
                    ->label('Con Seguro Social')
                    ->query(fn (Builder $query): Builder => $query->where('social_security', true)),

                Tables\Filters\Filter::make('has_housing_policy')
                    ->label('Con Política Habitacional')
                    ->query(fn (Builder $query): Builder => $query->where('housing_policy', true)),

                Tables\Filters\Filter::make('active_ministers')
                    ->label('Ministros Activos')
                    ->query(fn (Builder $query): Builder => $query->where('active', true)),

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
                
                Tables\Actions\EditAction::make()
                    ->hidden(fn () => !auth()->user()->hasAnyRole([
                        'Obispo Presidente',
                        'Secretario Nacional',
                        'Tesorero Nacional',
                        'Administrador',
                    ])),
                Tables\Actions\Action::make('generateCarnet')
                    ->icon('heroicon-o-identification') // Ícono tipo carnet
                    ->modalHeading('Generar Carnet')
                    ->modalSubheading('¿Estás seguro de que deseas generar el carnet para este pastor?')
                    ->modalButton('Generar')
                    ->hidden(fn () => !auth()->user()->hasAnyRole([
                        'Obispo Presidente',
                        'Secretario Nacional',
                        'Tesorero Nacional',
                        'Administrador',
                    ]))
                    ->action(function (Pastor $record) {
                        // Instancia el servicio
                        $carnetService = app(\App\Services\CarnetService::class);

                        try {
                            $result = $carnetService->generateCarnet($record);

                            if (!empty($result)) {
                                // Crear un archivo ZIP
                                $zipFilePath = storage_path("app/public/carnets/{$record->number_cedula}_carnets.zip");
                                $zip = new ZipArchive();
                
                                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                                    // Agregar los carnets al ZIP
                                    $zip->addFile(storage_path("app/public/carnets/{$record->number_cedula}_carnet_front.png"), "carnet_front.png");
                                    $zip->addFile(storage_path("app/public/carnets/{$record->number_cedula}_carnet_back.png"), "carnet_back.png");
                                    $zip->close();
                
                                    // Generar notificación con el enlace al ZIP
                                    Notification::make()
                                        ->title('El carnet se generó exitosamente.')
                                        ->body("
                                            <p>Descarga el archivo con los carnets generados:</p>
                                            <a href='" . Storage::url("carnets/{$record->number_cedula}_carnets.zip") . "' target='_blank'>Descargar Carnets</a>
                                        ")
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception("No se pudo crear el archivo ZIP.");
                                }
                            } else {
                                Notification::make()
                                    ->title('Error al generar el carnet.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Hubo un error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),



            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            //->emptyStateIcon('heroicon-o-user') // Icono para el estado vacío
            ->emptyStateHeading('Aun no has sido registrado como pastor.')
            ->emptyStateDescription('Contacte al Secretario de su Sector para que asocie tu usuario con su registro pastoral.')
            ->emptyStateActions([
                //
            ]);
    }

    protected function generateCarnet(Pastor $pastor)
    {
        // Instancia el servicio
        $carnetService = app(\App\Services\CarnetService::class);

        try {
            $result = $carnetService->generateCarnet($pastor);

            if (!empty($result)) {
                Filament::notify('success', "El carnet se generó exitosamente. <br>
                    <a href='{$result['front']}' target='_blank'>Frente del Carnet</a><br>
                    <a href='{$result['back']}' target='_blank'>Reverso del Carnet</a>");
            } else {
                Filament::notify('danger', 'Error al generar el carnet.');
            }
        } catch (\Exception $e) {
            Filament::notify('danger', 'Hubo un error: ' . $e->getMessage());
        }
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