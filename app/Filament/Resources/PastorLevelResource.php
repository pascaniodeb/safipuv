<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PastorLevelResource\Pages;
use App\Filament\Resources\PastorLevelResource\RelationManagers;
use App\Models\PastorLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\SecretaryNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PastorLevelResource extends Resource
{
    use SecretaryNationalAccess;
    
    protected static ?string $model = PastorLevel::class;

    protected static ?int $navigationSort = 6; // Orden

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Niveles';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('licence_id')
                            ->label('Licencia')
                            ->relationship('pastorLicence', 'name') // Relación con PastorLicence
                            ->searchable() // Habilita búsqueda
                            ->placeholder('Seleccione una licencia')
                            ->required()
                            ->helperText('Seleccione la licencia asociada al nivel de pastor.'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Nivel')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ingrese el nombre del nivel')
                            ->helperText('Ejemplo: Broce, Plata, Titanio, etc.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->maxLength(500)
                            ->placeholder('Describa brevemente este nivel')
                            ->helperText('Opcional. Máximo 500 caracteres.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2), // Organiza los campos en dos columnas

                Forms\Components\Section::make('Detalles del Nivel')
                    ->schema([
                        Forms\Components\TextInput::make('number')
                            ->label('Número')
                            ->numeric()
                            ->required()
                            ->placeholder('Ejemplo: 1, 2, 3...')
                            ->helperText('Indique el número asociado al nivel.'),

                        Forms\Components\TextInput::make('anosmin')
                            ->label('Años Mínimos')
                            ->numeric()
                            ->required()
                            ->placeholder('Ejemplo: 3')
                            ->helperText('Ingrese los años mínimos de experiencia requeridos.'),

                        Forms\Components\TextInput::make('anosmax')
                            ->label('Años Máximos')
                            ->numeric()
                            ->nullable()
                            ->placeholder('Ejemplo: 10')
                            ->helperText('Opcional. Deje vacío si no aplica.'),
                    ])
                    ->columns(3), // Organiza los campos en tres columnas
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pastorLicence.name')
                    ->label('Licencia')
                    ->sortable()
                    ->searchable()
                    ->toggleable(), // Permite mostrar u ocultar la columna

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Nivel')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50) // Limita el texto a 50 caracteres
                    ->toggleable(), // Permite mostrar u ocultar la columna
                Tables\Columns\BadgeColumn::make('number')
                    ->label('Número')
                    ->sortable()
                    ->color(fn ($state) => $state % 2 === 0 ? 'primary' : 'success'), // Aplica colores alternados
                    Tables\Columns\TextColumn::make('anosmin')
                    ->label('Años Mínimos')
                    ->sortable(),

                Tables\Columns\TextColumn::make('anosmax')
                    ->label('Años Máximos')
                    ->sortable()
                    ->toggleable(), // Permite mostrar u ocultar la columna

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d-m-Y H:i') // Formato personalizado
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto por defecto

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d-m-Y H:i') // Formato personalizado
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto por defecto
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
            'index' => Pages\ListPastorLevels::route('/'),
            'create' => Pages\CreatePastorLevel::route('/create'),
            'edit' => Pages\EditPastorLevel::route('/{record}/edit'),
        ];
    }
}