<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Role;
use App\Traits\RestrictToAdmin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    use RestrictToAdmin;

    protected static ?string $model = \Spatie\Permission\Models\Role::class;

    public static function getPluralModelLabel(): string
    {
        return 'Roles';
    }

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationGroup(): ?string
    {
        return 'Administración';
    }

    public static function getNavigationSort(): ?int
    {
        return 1; // Prioridad más alta
    }


    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Rol')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre del Rol')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ejemplo: Administrador'),

                    Forms\Components\Select::make('group')
                        ->label('Grupo de Roles')
                        ->options([
                            'NACIONAL' => 'Nacional',
                            'REGIONAL' => 'Regional',
                            'DISTRITAL' => 'Distrital',
                            'SECTORIAL' => 'Sectorial',
                        ])
                        ->required()
                        ->placeholder('Selecciona un grupo'),

                    Forms\Components\CheckboxList::make('permissions')
                        ->label('Permisos')
                        ->relationship('permissions', 'name')
                        ->columns(2)
                        ->helperText('Selecciona los permisos asociados a este rol'),
                ])
                ->columns(2) // Configuración para mostrar en dos columnas
                ->collapsible(), // Permite colapsar la sección si lo deseas
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nombre del Rol')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('group')
                ->label('Grupo de Roles')
                ->sortable(),

            Tables\Columns\BadgeColumn::make('permissions.name')
                ->label('Permisos')
                ->getStateUsing(fn ($record) => $record->permissions->pluck('name')->join(', '))
                ->colors([
                    'primary',
                ])
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Creado el')
                ->dateTime()
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('group')
                ->label('Grupo de Roles')
                ->options([
                    'NACIONAL' => 'Nacional',
                    'REGIONAL' => 'Regional',
                    'DISTRITAL' => 'Distrital',
                    'SECTORIAL' => 'Sectorial',
                ]),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}