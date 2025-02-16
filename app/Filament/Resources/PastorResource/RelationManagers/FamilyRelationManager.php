<?php

namespace App\Filament\Resources\PastorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
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
                            ->required()
                            ->maxLength(255)
                            ->unique(),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(),
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