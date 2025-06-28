<?php

namespace App\Filament\Resources\PastorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FamilyRelationManager extends RelationManager
{
    protected static string $relationship = 'families';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return 'Mis Familiares'; // Título personalizado del encabezado
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\FileUpload::make('photo_spouse')
                            ->label('Foto del Cónyuge')
                            ->image()
                            ->imageEditor()
                            ->directory('spouses')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('lastname')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('birthdate')
                            ->label('Fecha de Nacimiento')
                            ->required(),

                        Forms\Components\TextInput::make('birthplace')
                            ->label('Lugar de Nacimiento')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('number_cedula')
                            ->label('Cédula de Identidad')
                            //->required()
                            ->maxLength(255)
                            ->rules([
                                fn ($record) => Rule::unique('families', 'number_cedula')->ignore($record?->id),
                            ]),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            //->required()
                            ->maxLength(255)
                            ->rules([
                                fn ($record) => Rule::unique('families', 'email')->ignore($record?->id),
                            ]),

                        Forms\Components\Select::make('position_type_id')
                            ->label('Tipo de Cargo')
                            ->relationship('positionType', 'name')
                            ->required()
                            ->reactive()
                            ->native(false)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state == 5) {
                                    $set('current_position_id', null);
                                    $set('disable_current_position', true);
                                } else {
                                    $set('disable_current_position', false);
                                }
                            })
                            //->disabled(function () {
                                //return !Auth::user()->hasAnyRole([
                                    //'Administrador',
                                    //'Secretario Nacional',
                                    //'Tesorero Nacional',
                                    //'Secretario Regional',
                                    //'Secretario Sectorial',
                                    //'Pastor',
                                //]);
                            //})
                            ->dehydrated(),
                        
                        Forms\Components\Select::make('current_position_id')
                            ->label('Cargo Actual')
                            ->searchable()
                            ->options(function (callable $get) {
                                $positionTypeId = $get('position_type_id');
                                if ($positionTypeId && $positionTypeId != 5) {
                                    return \App\Models\CurrentPosition::where('position_type_id', $positionTypeId)
                                        ->where('gender_id', 2)
                                        ->pluck('name', 'id');
                                }
                                return [];
                            })
                            ->placeholder('Selecciona una posición')
                            ->disabled(fn (callable $get) => $get('disable_current_position') ?? false)
                            ->native(false)
                            ->reactive()
                            ->dehydrated(),
                        
                        
                    ])
                    ->columns(2), // Distribuye en dos columnas

                Forms\Components\Section::make('Información Familiar')
                    ->schema([
                        Forms\Components\Select::make('relation_id')
                            ->label('Parentesco')
                            ->relationship('relation', 'name')
                            ->required()
                            ->native(false)
                            ->placeholder('Selecciona un parentesco'),

                        Forms\Components\Select::make('gender_id')
                            ->label('Género')
                            ->relationship('gender', 'name')
                            ->required()
                            ->native(false)
                            ->placeholder('Selecciona un género'),

                        Forms\Components\Select::make('nationality_id')
                            ->label('Nacionalidad')
                            ->relationship('nationality', 'name')
                            ->required()
                            ->native(false)
                            ->placeholder('Selecciona una nacionalidad'),

                        Forms\Components\Select::make('marital_status_id')
                            ->label('Estado Civil')
                            ->relationship('maritalStatus', 'name')
                            ->native(false)
                            ->placeholder('Selecciona un estado civil')
                            ->required(), // Si el campo es obligatorio


                        Forms\Components\Select::make('blood_type_id')
                            ->label('Tipo de Sangre')
                            ->relationship('bloodType', 'name')
                            ->native(false)
                            ->placeholder('Selecciona un tipo de sangre'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('phone_mobile')
                            ->label('Teléfono Móvil')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone_house')
                            ->label('Teléfono de Casa')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información Académica')
                    ->schema([
                        Forms\Components\Select::make('academic_level_id')
                            ->label('Nivel Académico')
                            ->relationship('academicLevel', 'name')
                            ->native(false)
                            ->placeholder('Selecciona un nivel académico'),

                        Forms\Components\TextInput::make('career')
                            ->label('Carrera')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);

    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Description')
            ->columns([
                Tables\Columns\TextColumn::make('relation.name')
                    ->label('Parentesco')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lastname')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('gender.name')
                    ->label('Género')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nationality.name')
                    ->label('Nacionalidad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('number_cedula')
                    ->label('Cédula')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bloodType.name')
                    ->label('Tipo de Sangre')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('maritalStatus.name')
                    ->label('Estado Civil')
                    ->sortable(),

                Tables\Columns\TextColumn::make('academicLevel.name')
                    ->label('Nivel Académico')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('career')
                    ->label('Carrera')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone_mobile')
                    ->label('Teléfono Móvil')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone_house')
                    ->label('Teléfono de Casa')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('birthdate')
                    ->label('Fecha de Nacimiento')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('birthplace')
                    ->label('Lugar de Nacimiento')
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Familiar')
                    ->modalHeading('Nuevo Familiar'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}