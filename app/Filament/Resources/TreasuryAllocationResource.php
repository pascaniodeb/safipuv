<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TreasuryAllocationResource\Pages;
use App\Filament\Resources\TreasuryAllocationResource\RelationManagers;
use App\Models\TreasuryAllocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\VisibleToRolesTreasurer;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TreasuryAllocationResource extends Resource
{
    use VisibleToRolesTreasurer;
    
    protected static ?string $model = TreasuryAllocation::class;

    protected static ?string $navigationIcon = 'heroicon-s-chart-bar-square';

    public static function canCreate(): bool
    {
        return false; // Oculta el botón de crear
    }

    public static function canEdit($record): bool
    {
        return false; // Oculta el botón de "Editar"
    }


    public static function getPluralModelLabel(): string
    {
        return 'Deducciones';
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return match (true) {
            // TESORERO SECTORIAL: Solo ve registros de su sector
            $user->hasRole(['Tesorero Sectorial', 'Contralor Sectorial']) => $query
                ->selectRaw("
                    MIN(id) AS id,
                    treasury_id,
                    offering_category_id,
                    month,
                    SUM(amount) AS amount,
                    ROUND(AVG(percentage), 2) AS percentage
                ")
                ->whereHas('offeringReport.pastor', function ($q) use ($user) {
                    $q->where('sector_id', $user->sector_id);
                })
                ->whereHas('treasury', function ($q) {
                    $q->where('name', 'sectorial');
                })
                ->groupBy('treasury_id', 'offering_category_id', 'month'),

            // SUPERVISOR DISTRITAL: Solo ve registros de su distrito
            $user->hasRole('Supervisor Distrital') => $query
                ->selectRaw("
                    MIN(id) AS id,
                    treasury_id,
                    offering_category_id,
                    month,
                    SUM(amount) AS amount,
                    ROUND(AVG(percentage), 2) AS percentage
                ")
                ->whereHas('offeringReport.pastor', function ($q) use ($user) {
                    $q->where('district_id', $user->district_id);
                })
                ->whereHas('treasury', function ($q) {
                    $q->where('name', 'distrital');
                })
                ->groupBy('treasury_id', 'offering_category_id', 'month'),

            // TESORERO REGIONAL: Solo ve registros de su región
            $user->hasRole(['Tesorero Regional', 'Contralor Regional']) => $query
                ->selectRaw("
                    MIN(id) AS id,
                    treasury_id,
                    offering_category_id,
                    month,
                    SUM(amount) AS amount,
                    ROUND(AVG(percentage), 2) AS percentage
                ")
                ->whereHas('offeringReport.pastor', function ($q) use ($user) {
                    $q->where('region_id', $user->region_id);
                })
                ->whereHas('treasury', function ($q) {
                    $q->where('name', 'regional');
                })
                ->groupBy('treasury_id', 'offering_category_id', 'month'),

            // TESORERO NACIONAL: Ve todas las transacciones
            $user->hasRole(['Tesorero Nacional', 'Contralor Nacional']) => $query
                ->selectRaw("
                    MIN(id) AS id,
                    treasury_id,
                    offering_category_id,
                    month,
                    SUM(amount) AS amount,
                    ROUND(AVG(percentage), 2) AS percentage
                ")
                ->groupBy('treasury_id', 'offering_category_id', 'month'),

            // Para otros usuarios, no mostrar registros
            default => $query->whereNull('id'),
        };
    }

    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('month')
                ->label('Mes')
                ->default(now()->format('Y-m'))
                ->disabled()
                ->dehydrated(), // Para que lo guarde aunque esté deshabilitado
            
            Forms\Components\Select::make('offering_report_id')
                ->label('Reporte de Ofrenda')
                ->relationship('offeringReport', 'number_report')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('treasury_id')
                ->label('Tesorería Receptora')
                ->relationship('treasury', 'name')
                ->searchable()
                ->required(),
                
            Forms\Components\Select::make('offering_category_id')
                ->label('Categoría de Ofrenda')
                ->relationship('offeringCategory', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('amount')
                ->label('Monto Asignado')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('percentage')
                ->label('Porcentaje de Distribución')
                ->suffix('%')
                ->numeric()
                ->required(),

            Forms\Components\Textarea::make('remarks')
                ->label('Observaciones')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->label('Mes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('treasury.name')
                    ->label('Tesorería')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('offeringCategory.name')
                    ->label('Categoría de Ofrenda')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto Asignado')
                    ->money('VES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Porcentaje')
                    ->suffix('%')
                    ->sortable()
                    ->visible(fn () => auth()->user()->hasRole('Tesorero Nacional')),
                

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Asignación')
                    ->date('d/m/Y H:i') // Formato de fecha y hora
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Actualización')  
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // 🔹 Filtro por Mes (month)
                Tables\Filters\SelectFilter::make('month')
                    ->label('Mes')
                    ->options(
                        TreasuryAllocation::select('month')
                            ->distinct()
                            ->orderBy('month', 'desc')
                            ->pluck('month', 'month')
                            ->toArray()
                    )
                    ->searchable(),
            
                // 🔹 Filtro por Tesorería (treasury_id)
                Tables\Filters\SelectFilter::make('treasury_id')
                    ->label('Tesorería')
                    ->relationship('treasury', 'name')
                    ->searchable(),
            
                // 🔹 Filtro por Categoría de Ofrenda (offering_category_id)
                Tables\Filters\SelectFilter::make('offering_category_id')
                    ->label('Categoría de Ofrenda')
                    ->relationship('offeringCategory', 'name')
                    ->searchable(),
            
                // 🔹 Filtro por Rango de Monto (amount)
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Monto mínimo')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Monto máximo')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min_amount'] ?? null, fn ($q, $min) => $q->where('amount', '>=', $min))
                            ->when($data['max_amount'] ?? null, fn ($q, $max) => $q->where('amount', '<=', $max));
                    }),
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
            'index' => Pages\ListTreasuryAllocations::route('/'),
            'create' => Pages\CreateTreasuryAllocation::route('/create'),
            'edit' => Pages\EditTreasuryAllocation::route('/{record}/edit'),
        ];
    }
}