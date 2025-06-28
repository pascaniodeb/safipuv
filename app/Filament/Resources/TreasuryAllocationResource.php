<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TreasuryAllocationResource\Pages;
use App\Filament\Resources\TreasuryAllocationResource\RelationManagers;
use App\Models\TreasuryAllocation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Treasury;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\VisibleToRolesTreasurer;
use App\Traits\FiltersSectorsTrait;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TreasuryAllocationResource extends Resource
{
    use VisibleToRolesTreasurer, FiltersSectorsTrait;
    
    protected static ?string $model = TreasuryAllocation::class;

    protected static ?string $navigationIcon = 'heroicon-s-chart-bar-square';

    public static function canCreate(): bool
    {
        return false; // Oculta el botón de crear
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasRole('Tesorero Sectorial');
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
            // TESORERO SECTORIAL: Ve registros de su sector y lo que se envía al distrito, regional y nacional
            $user->hasRole(['Presbítero Sectorial','Tesorero Sectorial', 'Contralor Sectorial']) => $query
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
                $q->whereIn('name', ['sectorial', 'distrital', 'regional', 'nacional']);
            }) // 🔹 Filtra no solo el sectorial, sino también distrital, regional y nacional
            ->groupBy('treasury_id', 'offering_category_id', 'month')
            ->orderByRaw('MIN(id) ASC'),


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
                ->groupBy('treasury_id', 'offering_category_id', 'month')
                ->orderByRaw('MIN(id) ASC'),

            // TESORERO REGIONAL: Solo ve registros de su región
            $user->hasRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional']) => $query
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
                ->groupBy('treasury_id', 'offering_category_id', 'month')
                ->orderByRaw('MIN(id) ASC'),



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
                ->groupBy('treasury_id', 'offering_category_id', 'month')
                ->orderByRaw('MIN(id) ASC'),

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
            //->query(
                //static::getEloquentQuery()
                    //->with(['treasury', 'offeringCategory'])
            //)
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
                    ->label('Monto Bs.')
                    ->money('VES')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount_usd_by_user_level')
                    ->label('Monto USD')
                    ->state(fn (TreasuryAllocation $record) =>
                        ($monto = $record->getMontoDistribuidoEn('USD', auth()->user()))
                            ? '$' . number_format($monto, 2, '.', ',')
                            : '—'
                    ),

                Tables\Columns\TextColumn::make('amount_cop_by_user_level')
                    ->label('Monto COP')
                    ->state(fn (TreasuryAllocation $record) =>
                        ($monto = $record->getMontoDistribuidoEn('COP', auth()->user()))
                            ? number_format($monto, 2, '.', ',') . ' COP'
                            : '—'
                    ),


                
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Porcentaje')
                    ->suffix('%')
                    ->sortable()
                    ->visible(fn () => auth()->user()->hasRole('Administrador')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Asignación')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Actualización')  
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por Mes
                SelectFilter::make('month')
                    ->label('Mes')
                    ->options(
                        TreasuryAllocation::select('month')
                            ->distinct()
                            ->orderByDesc('month')
                            ->pluck('month')
                            ->mapWithKeys(fn ($mes) => [
                                $mes => Carbon::parse($mes . '-01')->translatedFormat('F/Y'),
                            ])
                            ->toArray()
                    )
                    ->default(
                        TreasuryAllocation::orderByDesc('month')->value('month')
                    )
                    ->searchable(),
    
                // Filtro por Tesorería
                SelectFilter::make('treasury_id')
                    ->label('Tesorería')
                    ->visible(fn () => !Auth::user()?->hasRole('Supervisor Distrital'))
                    ->options(function () {
                        $user = Auth::user();
                        $tesoreria = Treasury::where('level', $user->treasury_level ?? null)->first();

                        if (! $tesoreria) {
                            return [null => 'TODOS'];
                        }

                        return [
                            null => 'TODOS',
                            $tesoreria->id => $tesoreria->name ?: 'Tesorería sin nombre',
                        ];
                    })
                    ->default(function () {
                        $user = Auth::user();
                        return Treasury::where('level', $user->treasury_level ?? null)->first()?->id;
                    })
                    ->searchable(),

    
                // 🔹 Filtro por Sector (solo para roles altos)
                SelectFilter::make('sector_id')
                    ->label('Sector')
                    ->visible(fn () => Auth::user()?->hasAnyRole(['Administrador', 'Tesorero Nacional']))
                    ->options(static::getSectorsForCurrentUserStatic())
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value']) || ! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('offeringReport.pastor', function ($subQuery) use ($data) {
                            $subQuery->where('sector_id', $data['value']);
                        });
                    }),
            ])
            ->persistFiltersInSession() // Persistir filtros en la sesión
            
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