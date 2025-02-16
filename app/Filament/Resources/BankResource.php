<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankResource\Pages;
use App\Filament\Resources\BankResource\RelationManagers;
use App\Models\Bank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\TreasurerNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankResource extends Resource
{
    use TreasurerNationalAccess;
    
    protected static ?string $model = Bank::class;

    protected static ?int $navigationSort = 5; // Orden

    protected static ?string $navigationIcon = 'heroicon-s-building-office-2';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Bancos';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('location_id')
                    ->label('Ubicación')
                    ->relationship('location', 'name')
                    ->required(),

                Forms\Components\TextInput::make('bank_code')
                    ->label('Código del Banco')
                    ->numeric()
                    ->maxLength(4),

                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Banco')
                    ->required(),

                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(false),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Ubicación'),

                Tables\Columns\TextColumn::make('bank_code')
                    ->label('Código'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),

                Tables\Columns\BooleanColumn::make('active')
                    ->label('Activo'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Ubicación')
                    ->relationship('location', 'name'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBanks::route('/'),
        ];
    }
}