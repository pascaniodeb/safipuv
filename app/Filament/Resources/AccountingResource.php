<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingResource\Pages;
use App\Filament\Resources\AccountingResource\RelationManagers;
use App\Filament\Resources\AccountingResource\RelationManagers\AccountingCodeIncomeRelationManager;
use App\Filament\Resources\AccountingResource\RelationManagers\AccountingCodeEgressRelationManager;
use App\Models\Accounting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\TreasurerNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingResource extends Resource
{
    use TreasurerNationalAccess;
    
    protected static ?string $model = Accounting::class;

    protected static ?int $navigationSort = 4; // Orden

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Códigos Contables';
    }

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('treasury_id')
                    ->label('Tesorería')
                    ->relationship('treasury', 'name')
                    ->required(),
                
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la Contabilidad')
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->maxLength(100),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Contabilidad'),

                Tables\Columns\TextColumn::make('treasury.name')
                    ->label('Tesorería'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción'),
            ])
            ->filters([])
                //
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            RelationManagers\AccountingCodeIncomeRelationManager::class,
            RelationManagers\AccountingCodeEgressRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountings::route('/'),
            'create' => Pages\CreateAccounting::route('/create'),
            'edit' => Pages\EditAccounting::route('/{record}/edit'),
        ];
    }
}