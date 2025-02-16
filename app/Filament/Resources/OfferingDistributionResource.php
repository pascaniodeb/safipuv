<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferingDistributionResource\Pages;
use App\Filament\Resources\OfferingDistributionResource\RelationManagers;
use App\Models\OfferingDistribution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\TreasurerNationalAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferingDistributionResource extends Resource
{
    use TreasurerNationalAccess;
    
    protected static ?string $model = OfferingDistribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurar';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Distribuciones';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('offering_category_id')
                ->relationship('offeringCategory', 'name')
                ->label('Ofrenda')
                ->placeholder('Seleccione una ofrenda')
                ->options(\App\Models\OfferingCategory::orderBy('id')->pluck('name', 'id')) // Ordena las opciones
                ->native(false)
                ->required(),

            Forms\Components\Select::make('source_treasury_id')
                ->relationship('sourceTreasury', 'name')
                ->label('Tesorería de Origen')
                ->placeholder('Seleccione una tesorería')
                ->options(\App\Models\Treasury::orderBy('id')->pluck('name', 'id')) // Ordena las opciones
                ->native(false)
                ->required(),

            Forms\Components\Select::make('target_treasury_id')
                ->relationship('targetTreasury', 'name')
                ->label('Tesorería de Destino')
                ->placeholder('Seleccione una tesorería')
                ->options(\App\Models\Treasury::orderBy('id')->pluck('name', 'id')) // Ordena las opciones
                ->native(false)
                ->required(),
                
            Forms\Components\TextInput::make('percentage')
                ->numeric()
                ->label('Porcentaje')
                ->minValue(0)->maxValue(100)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('offeringCategory.name')
                    ->label('Ofrenda'),

                Tables\Columns\TextColumn::make('sourceTreasury.name')
                    ->label('Tesorería Origen'),

                Tables\Columns\TextColumn::make('targetTreasury.name')
                    ->label('Tesorería Destino'),
                    
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Porcentaje')
                    ->suffix('%')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
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
            'index' => Pages\ListOfferingDistributions::route('/'),
            'create' => Pages\CreateOfferingDistribution::route('/create'),
            'edit' => Pages\EditOfferingDistribution::route('/{record}/edit'),
        ];
    }
}