<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferingDistributionSubdivisionResource\Pages;
use App\Filament\Resources\OfferingDistributionSubdivisionResource\RelationManagers;
use App\Models\OfferingDistributionSubdivision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\TreasurerNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferingDistributionSubdivisionResource extends Resource
{
    use TreasurerNationalAccess;
    
    protected static ?string $model = OfferingDistributionSubdivision::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Sub-Deducciones';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('distribution_id')
                ->relationship('offeringDistribution', 'id') // Cambiar esto
                ->getOptionLabelFromRecordUsing(fn ($record) => 
                    "{$record->offeringCategory->name} - {$record->sourceTreasury->name} → {$record->targetTreasury->name}"
                ) // Muestra una etiqueta más descriptiva
                //->searchable()
                ->required(),
            Forms\Components\TextInput::make('subdivision_name')->required(),
            Forms\Components\TextInput::make('percentage')
                ->numeric()
                ->minValue(0)->maxValue(100)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('offeringDistribution.offeringCategory.name')
                    ->label('Categoría de Ofrenda')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('offeringDistribution.sourceTreasury.name')
                    ->label('Tesorería Origen')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('offeringDistribution.targetTreasury.name')
                    ->label('Tesorería Destino')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subdivision_name')->sortable()->label('Subdivisión'),
                Tables\Columns\TextColumn::make('percentage')->suffix('%')->sortable()->label('Porcentaje'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListOfferingDistributionSubdivisions::route('/'),
            'create' => Pages\CreateOfferingDistributionSubdivision::route('/create'),
            'edit' => Pages\EditOfferingDistributionSubdivision::route('/{record}/edit'),
        ];
    }
}