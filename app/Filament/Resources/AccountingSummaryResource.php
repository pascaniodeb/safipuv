<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingSummaryResource\Pages;
use App\Filament\Resources\AccountingSummaryResource\RelationManagers;
use App\Models\AccountingSummary;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use App\Traits\VisibleToRolesTreasurer;
use App\Traits\HasAccountingAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingSummaryResource extends Resource
{
    use VisibleToRolesTreasurer, HasAccountingAccess;
    
    protected static ?string $model = AccountingSummary::class;

    public static function getPluralModelLabel(): string
    {
        return 'Libro Mayor';
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
        \Log::info("AccountingSummary getEloquentQuery - EJECUTÁNDOSE");
        
        $query = parent::getEloquentQuery()->accessibleRecords();
        
        \Log::info("AccountingSummary getEloquentQuery - Query aplicada, SQL: " . $query->toSql());
        \Log::info("AccountingSummary getEloquentQuery - Bindings: " . json_encode($query->getBindings()));
        
        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public function ejecutar()
    {
        $accounting = $this->getUserAccounting();

        if (! $accounting) {
            throw new \Exception('No se encontró contabilidad asociada al usuario.');
        }

        // ¡Ahora puedes usar $accounting->id!
    }

    public static function isMonthClosed(string $month, int $accountingId): bool
    {
        $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Busca si están cerradas todas las divisas
        $monedas = DB::table('accounting_transactions')
            ->where('accounting_id', $accountingId)
            ->whereBetween('transaction_date', [$start, $end])
            ->where('is_closed', true)
            ->where('description', "Saldo inicial de {$month}")
            ->pluck('currency')
            ->unique()
            ->toArray();

        // Si están las tres monedas, entonces está cerrado
        return count(array_intersect(['VES', 'USD', 'COP'], $monedas)) === 3;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /* 📆 MES ------------------------------------------------------ */
                Tables\Columns\TextColumn::make('month')
                    ->label('Mes')
                    ->sortable()
                    ->formatStateUsing(fn (string $state) =>              // '2025-02' → '02/2025'
                        Carbon::createFromFormat('Y-m', $state)->format('m/Y')
                    ),

                /* 💱 MONEDA --------------------------------------------------- */
                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'USD' => 'info',
                        'COP' => 'warning',
                        'VES' => 'success',
                        default => 'gray',
                    }),

                /* 💰 INGRESOS (total_income) ---------------------------------- */
                Tables\Columns\TextColumn::make('total_income')
                    ->label('Ingresos')
                    ->money(fn ($record) => $record->currency)             // Formato monetario nativo
                    ->color('success')
                    ->alignEnd(),

                /* 💸 EGRESOS (total_expense) --------------------------------- */
                Tables\Columns\TextColumn::make('total_expense')
                    ->label('Egresos')
                    ->money(fn ($record) => $record->currency)
                    ->color('danger')
                    ->alignEnd(),

                /* 🧾 SALDO (saldo) ------------------------------------------- */
                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money(fn ($record) => $record->currency)
                    ->color('primary')
                    ->alignEnd(),
            ])

            /* Orden predeterminado ↓ */
            ->defaultSort('month', 'desc')

            /* Filtros opcionales -------------------------------------------- */
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options([
                        'VES' => 'VES',
                        'USD' => 'USD',
                        'COP' => 'COP',
                    ])
                    ->native(false),
            
                Tables\Filters\SelectFilter::make('month')
                    ->label('Mes contable')
                    ->options(function () {
                        // Obtener solo los meses accesibles para el usuario actual
                        $user = auth()->user();
                        if (!$user) return [];

                        // Crear instancia temporal para usar el trait
                        $tempInstance = new class {
                            use \App\Traits\HasAccountingAccess;
                        };
                        
                        $accounting = $tempInstance->getUserAccounting();
                        if (!$accounting) return [];

                        // Query base con filtros geográficos
                        $query = \App\Models\AccountingSummary::where('accounting_id', $accounting->id);

                        // Aplicar filtros geográficos
                        if ($user->hasAnyRole(['Tesorero Sectorial', 'Contralor Sectorial']) && $user->sector_id) {
                            $query->where('sector_id', $user->sector_id);
                        } elseif ($user->hasRole('Supervisor Distrital') && $user->district_id) {
                            $query->where('district_id', $user->district_id);
                        } elseif ($user->hasAnyRole(['Tesorero Regional', 'Contralor Regional']) && $user->region_id) {
                            $query->where('region_id', $user->region_id);
                        }

                        return $query
                            ->select('month')
                            ->distinct()
                            ->orderByDesc('month')
                            ->pluck('month')
                            ->mapWithKeys(fn ($mes) => [
                                $mes => Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F/Y'),
                            ])
                            ->toArray();
                    })
                    ->default(function () {
                        // Obtener el último mes accesible para el usuario
                        $user = auth()->user();
                        if (!$user) return null;

                        $tempInstance = new class {
                            use \App\Traits\HasAccountingAccess;
                        };
                        
                        $accounting = $tempInstance->getUserAccounting();
                        if (!$accounting) return null;

                        $query = \App\Models\AccountingSummary::where('accounting_id', $accounting->id);

                        if ($user->hasAnyRole(['Tesorero Sectorial', 'Contralor Sectorial']) && $user->sector_id) {
                            $query->where('sector_id', $user->sector_id);
                        } elseif ($user->hasRole('Supervisor Distrital') && $user->district_id) {
                            $query->where('district_id', $user->district_id);
                        } elseif ($user->hasAnyRole(['Tesorero Regional', 'Contralor Regional']) && $user->region_id) {
                            $query->where('region_id', $user->region_id);
                        }

                        return $query->orderByDesc('month')->value('month');
                    })
                    ->native(false),
            ])
            

            /* Acciones / bulkActions (si las necesitas) --------------------- */
            ->actions([
                // Ej.: Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // ...
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
            'index' => Pages\ListAccountingSummaries::route('/'),
            'create' => Pages\CreateAccountingSummary::route('/create'),
            'edit' => Pages\EditAccountingSummary::route('/{record}/edit'),
        ];
    }
}