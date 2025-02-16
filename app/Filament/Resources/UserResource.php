<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Traits\RestrictUserResourceAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    use RestrictUserResourceAccess;
    
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 1; // Orden

    // Título principal del index
    public static function getPluralModelLabel(): string
    {
        $user = auth()->user();

        return $user->hasRole(['Administrador', 'Secretario Nacional', 'Tesorero Nacional']) ? 'Usuarios' : 'Mi Perfil';
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        return $user->hasRole(['Administrador', 'Secretario Nacional', 'Tesorero Nacional']) ? 'Usuarios' : 'Mi Perfil'; 
    }


    protected static ?string $navigationIcon = 'heroicon-s-user-circle';

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

    public static function getNavigationSort(): ?int
    {
        return 0;
    }


    public static function getNavigationBadge(): ?string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::$model;

        return (string) $modelClass::count(); // Personaliza según sea necesario
    }

    public static function getSearchable(): array
    {
        return [
            'name',
            'lastname',
            'username',
            'email',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Si el usuario NO tiene los roles especificados, solo mostrar su propio usuario
        if (!$user->hasAnyRole(['Obispo Presidente', 'Secretario Nacional', 'Tesorero Nacional', 'Administrador'])) {
            return User::where('id', $user->id);
        }

        // Si tiene alguno de los roles, mostrar todos los usuarios
        return parent::getEloquentQuery();
    }
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección: Información de Ubicación
                Forms\Components\Section::make('Información de Ubicación')
                    ->schema([
                        Forms\Components\Select::make('region_id')
                            ->label('Región')
                            ->relationship('region', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('district_id', null))
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Administrador',
                                    'Secretario Nacional',
                                    'Tesorero Nacional', 
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
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Administrador',
                                    'Secretario Nacional',
                                    'Tesorero Nacional', 
                                ]);
                            })
                            ->dehydrated(),

                        Forms\Components\Select::make('sector_id')
                            ->label('Sector')
                            ->options(fn (callable $get) =>
                                \App\Models\Sector::where('district_id', $get('district_id'))->pluck('name', 'id'))
                            ->required()
                            ->disabled(fn (callable $get) => !$get('district_id'))
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole([
                                    'Administrador',
                                    'Secretario Nacional',
                                    'Tesorero Nacional', 
                                ]);
                            })
                            ->dehydrated(),
                    ])
                    ->columns(3), // Tres columnas para la sección de ubicación

                // Sección: Información del Rol
                Forms\Components\Section::make('Información del Rol')
                    ->schema([
                        // Grupo de Roles
                        Forms\Components\Select::make('role_group')
                            ->label('Grupo de Roles')
                            ->options(function () {
                                $options = [
                                    'REGIONAL' => 'Regional',
                                    'DISTRITAL' => 'Distrital',
                                    'SECTORIAL' => 'Sectorial',
                                ];

                                // Mostrar todas las opciones al Administrador
                                if (Auth::user()->hasRole('Administrador')) {
                                    $options = [
                                        'NACIONAL' => 'Nacional',
                                        'REGIONAL' => 'Regional',
                                        'DISTRITAL' => 'Distrital',
                                        'SECTORIAL' => 'Sectorial',
                                    ];
                                }

                                return $options;
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('role_name', null))
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record) {
                                    $role = $record->roles->first();
                                    if ($role) {
                                        $set('role_group', $role->group);
                                    }
                                }
                            })
                            ->disabled(function ($record) {
                                $user = Auth::user();
                                // Deshabilitar para el usuario actual si no tiene los roles permitidos
                                return !$user->hasRole(['Administrador', 'Secretario Nacional', 'Tesorero Nacional']) || 
                                       ($user->hasRole(['Secretario Nacional', 'Tesorero Nacional']) && $record->id === $user->id);
                            }),

                        // Roles
                        Forms\Components\Select::make('role_name')
                            ->label('Rol')
                            ->options(function (callable $get) {
                                $group = $get('role_group');
                                $user = Auth::user();
                                $query = \Spatie\Permission\Models\Role::query();

                                if ($group) {
                                    $query->where('group', $group);
                                }

                                // Secretario Nacional solo puede ver roles específicos
                                if ($user->hasRole('Secretario Nacional')) {
                                    $query->whereIn('group', ['REGIONAL', 'DISTRITAL', 'SECTORIAL']);
                                }

                                return $query->pluck('name', 'name');
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Seleccione un rol')
                            ->disabled(fn () => !Auth::user()->hasAnyRole(['Administrador', 'Secretario Nacional']))
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record) {
                                    $role = $record->roles->first();
                                    if ($role) {
                                        $set('role_name', $role->name);
                                    }
                                }
                            })
                            ->dehydrateStateUsing(function ($state, $record) {
                                if ($record) {
                                    $record->syncRoles([$state]);
                                }
                                return $state;
                            })
                            ->disabled(function ($record) {
                                $user = Auth::user();
                                // Deshabilitar para el usuario actual si no tiene los roles permitidos
                                return !$user->hasRole(['Administrador', 'Secretario Nacional', 'Tesorero Nacional']) || 
                                       ($user->hasRole(['Secretario Nacional', 'Tesorero Nacional']) && $record->id === $user->id);
                            }),
                    ])
                    ->columns(2),



                // Sección: Información Personal
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('lastname')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('nationality_id')
                            ->label('Nacionalidad')
                            ->relationship('nationality', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('username')
                            ->label('Cédula')
                            ->numeric()
                            ->unique(table: 'users', column: 'username', ignoreRecord: true)
                            ->required()
                            ->maxLength(8)
                            ->disabledOn('edit'), // Deshabilitar en la edición

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->unique(table: 'users', column: 'email', ignoreRecord: true)
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null) // Encripta la contraseña si no está vacía
                            ->dehydrated(fn ($state) => filled($state)) // Solo se envía al servidor si se proporciona un valor
                            ->required(fn (?string $operation) => $operation === 'create') // Obligatorio solo durante la creación
                            ->placeholder(fn (?string $operation) => $operation === 'edit' ? 'Deja vacío para mantener la actual' : null),

                        Forms\Components\Toggle::make('active')
                            ->label('Activo')
                            ->default(true)
                            ->disabled(function () {
                                // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                return !Auth::user()->hasAnyRole(['
                                    Administrador',
                                    'Secretario Nacional',
                                    'Tesorero Nacional', 
                                ]);
                            })
                            ->dehydrated(),

                        Forms\Components\FileUpload::make('profile_photo')
                            ->label('Foto de Perfil')
                            ->directory('profile_photos')
                            ->image()
                            ->maxSize(2048) // Máximo 2 MB
                            ->placeholder('Subir una foto'), 
                        

                        
                    ])
                    ->columns(2), // Dos columnas para la sección de información personal
        ]);

    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Garantiza que siempre haya una contraseña en la creación
        if (empty($data['password'])) {
            $data['password'] = bcrypt('safipuv'); // Valor predeterminado si es necesario
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Durante la edición, si el campo está vacío, no cambia la contraseña
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\ImageColumn::make('profile_photo')
                    ->label('Foto')
                    ->rounded(),

                // Nombre y Apellido
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lastname')
                    ->label('Apellido')
                    ->searchable(),

                // ID Personal
                Tables\Columns\TextColumn::make('username')
                    ->label('Cédula')
                    ->searchable()
                    ->sortable(),

                // Nacionalidad
                Tables\Columns\TextColumn::make('nationality.name')
                    ->label('Nacionalidad')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Correo Electrónico
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('roles.name')
                    ->label('Rol')
                    ->getStateUsing(fn ($record) => $record->roles->pluck('name')->join(', '))
                    ->colors([
                        'primary',
                    ])
                    ->sortable(),

                    // Región
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Región')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Distrito
                Tables\Columns\TextColumn::make('district.name')
                    ->label('Distrito')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Sector
                Tables\Columns\TextColumn::make('sector.name')
                    ->label('Sector')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Activo
                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                // Verificado
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verificado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Fechas de Creación y Actualización
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

                // Eliminado (Soft Deletes)
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}