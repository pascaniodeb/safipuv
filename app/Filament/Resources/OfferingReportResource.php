<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferingReportResource\Pages;
use App\Filament\Resources\OfferingReportResource\RelationManagers;
use App\Models\Offering;
use App\Models\OfferingReport;
use App\Models\OfferingItem;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\EditAction;
use App\Services\ReporteMensualOfrendasService;
use Filament\Tables;
use App\Models\Pastor;
use App\Models\Church;
use App\Models\Treasury;
use App\Models\BankTransaction;
use App\Models\Bank;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\TextEntry;
use App\Traits\OfferingReportFilterTrait;
use App\Traits\Filters\HasUbicacionGeograficaFilters;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferingReportResource extends Resource
{
    use OfferingReportFilterTrait;
    use HasUbicacionGeograficaFilters;
    
    protected static ?string $model = OfferingReport::class;

    protected static ?string $navigationIcon = 'heroicon-s-pencil-square';

    public static function getPluralModelLabel(): string
    {
        return 'Registrar Ofrendas';
    }

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();

        if ($user) {
            $roleName = $user->getRoleNames()->first();
            return $roleName ? '' . $roleName : 'Modulos';
        }

        return 'Modulos';
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('Tesorero Sectorial');
    }

    public static function canView($record): bool
    {
        return true;
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        // Datos del Pastor
                        Forms\Components\Section::make('Datos del Pastor e Iglesia')
                            ->schema(static::getDetailsFormSchema())
                            ->columns(2),

                        // Ofrendas
                        Forms\Components\Section::make('Ofrendas')
                            ->headerActions([
                                Action::make('reset')
                                    ->modalHeading('¿Estás seguro?')
                                    ->modalDescription('Esto eliminará todos los productos de la orden.')
                                    ->requiresConfirmation()
                                    ->color('danger')
                                    ->action(fn (Forms\Set $set) => $set('offering_items', [])),
                            ])
                            ->schema([
                                static::getItemsRepeater(),
                            ]),

                        // Totales Globales
                        Forms\Components\Section::make('Totales Globales')
                            ->schema([
                                Forms\Components\ToggleButtons::make('status')
                                    ->inline()
                                    ->options([
                                        'pendiente' => 'Pendiente',
                                        'aprobado' => 'Aprobado',
                                    ])
                                    ->colors([
                                        'pendiente' => 'warning',
                                        'aprobado' => 'success',
                                    ])
                                    ->required()
                                    ->dehydrated()
                                    ->disabled(function () {
                                        return !Auth::user()->hasAnyRole([
                                            'Tesorero Sectorial', 
                                        ]);
                                    }),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('total_bs')
                                            ->label('Total de Bolívares')
                                            ->prefix('Bs')
                                            ->reactive()
                                            ->live()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                                            ->extraAttributes([
                                                'class' => 'font-semibold text-right'
                                            ]),

                                        Forms\Components\TextInput::make('total_usd')
                                            ->label('Total de Dólares')
                                            ->prefix('$')
                                            ->reactive()
                                            ->live()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                                            ->extraAttributes([
                                                'class' => 'font-semibold text-right'
                                            ]),
                                        
                                        Forms\Components\TextInput::make('total_cop')
                                            ->label('Total de Pesos')
                                            ->prefix('COP')
                                            ->reactive()
                                            ->live()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                                            ->extraAttributes([
                                                'class' => 'font-semibold text-right'
                                            ]),
                                    ]),
                                    
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('total_usd_to_bs')
                                            ->label('Total USD a Bs')
                                            ->prefix('Bs')
                                            ->reactive()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                                            ->helperText('USD convertido a Bs')
                                            ->extraAttributes([
                                                'class' => 'text-right'
                                            ]),

                                        Forms\Components\TextInput::make('total_cop_to_bs')
                                            ->label('Total COP a Bs')
                                            ->prefix('Bs')
                                            ->reactive()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                                            ->helperText('COP convertido a Bs')
                                            ->extraAttributes([
                                                'class' => 'text-right'
                                            ]),

                                        Forms\Components\TextInput::make('grand_total_bs')
                                            ->label('Gran Total en Bs')
                                            ->prefix('Bs')
                                            ->reactive()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated()
                                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                                            ->helperText('Suma total en Bolívares')
                                            ->extraAttributes([
                                                'class' => 'font-bold text-right text-lg',
                                                'style' => 'background-color: #f3f4f6;'
                                            ]),
                                    ]),
                                    
                                // Información adicional de tasas aplicadas
                                Forms\Components\Placeholder::make('exchange_rates_info')
                                    ->label('Tasas de cambio aplicadas')
                                    ->content(function ($get) {
                                        $usdRate = (float) str_replace(',', '.', $get('usd_rate') ?? 0);
                                        $copRate = (float) str_replace(',', '.', $get('cop_rate') ?? 0);
                                        
                                        if ($usdRate > 0 || $copRate > 0) {
                                            $info = '<div class="text-sm space-y-1">';
                                            
                                            if ($usdRate > 0) {
                                                $info .= '<div>• <strong>USD:</strong> 1 USD = ' . number_format($usdRate, 4, ',', '.') . ' Bs</div>';
                                            }
                                            
                                            if ($copRate > 0) {
                                                $info .= '<div>• <strong>COP:</strong> ' . number_format($copRate, 2, ',', '.') . ' COP = 1 Bs</div>';
                                            }
                                            
                                            $info .= '</div>';
                                            
                                            return new \Illuminate\Support\HtmlString($info);
                                        }
                                        
                                        return 'No hay tasas de cambio configuradas';
                                    })
                                    ->columnSpan('full'),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->persistCollapsed()
                            ->description('Resumen de todos los montos registrados'),
                    ])
                    ->columnSpan(['lg' => fn (?OfferingReport $record) => $record === null ? 3 : 3]),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('month')
                    ->label('Mes')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('F Y')),

                // Usar el accessor para mostrar el nombre del pastor
                TextColumn::make('pastor_display_name')
                    ->label('Pastor/Responsable')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->whereHas('pastor', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%")
                                ->orWhere('number_cedula', 'like', "%{$search}%");
                            })
                            ->orWhere('pastor_name_manual', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('pastors', 'offering_reports.pastor_id', '=', 'pastors.id')
                            ->orderBy('pastors.name', $direction)
                            ->select('offering_reports.*');
                    })
                    ->description(fn ($record) => $record->historical_note)
                    ->wrap()
                    ->copyable(),

                // Badge de estado del pastor
                TextColumn::make('pastor_status')
                    ->label('Estado Pastor')
                    ->badge()
                    ->formatStateUsing(fn ($record) => match($record->pastor_status) {
                        'sin_pastor' => 'Sin Pastor',
                        'trasladado' => 'Trasladado',
                        'activo' => 'Activo',
                        default => 'Sin Info',
                    })
                    ->color(fn ($record) => match($record->pastor_status) {
                        'sin_pastor' => 'warning',
                        'trasladado' => 'danger',
                        'activo' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),

                // Cédula del pastor (mostrar N/A si no hay pastor)
                TextColumn::make('pastor.number_cedula')
                    ->label('Cédula')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->church_without_pastor ? 'N/A' : ($state ?? 'Sin cédula')
                    ),
                    
                TextColumn::make('status')
                    ->badge()
                    ->label('Estado')
                    ->sortable()
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'aprobado',
                    ]),

                TextColumn::make('church.name')
                    ->label('Iglesia')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('region.name')
                    ->label('Región')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('district.name')
                    ->label('Distrito')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sector.name')
                    ->label('Sector')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_bs')
                    ->label('Total Bs')
                    ->money('VES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_usd')
                    ->label('Total USD')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_cop')
                    ->label('Total COP')
                    ->money('COP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_usd_to_bs')
                    ->label('Total USD a Bs')
                    ->money('VES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_cop_to_bs')
                    ->label('Total COP a Bs')
                    ->money('VES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('grand_total_bs')
                    ->label('Gran Total Bs')
                    ->money('VES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Agregar estas columnas en el método table() después de 'grand_total_bs'

                // DIEZMOS
                TextColumn::make('diezmos_bs')
                    ->label('Diezmos (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'DIEZMOS'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('diezmos_usd')
                    ->label('Diezmos (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'DIEZMOS'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('diezmos_cop')
                    ->label('Diezmos (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'DIEZMOS'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // EL PODER DEL UNO
                TextColumn::make('poder_uno_bs')
                    ->label('Poder del Uno (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'EL PODER DEL UNO'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('poder_uno_usd')
                    ->label('Poder del Uno (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'EL PODER DEL UNO'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('poder_uno_cop')
                    ->label('Poder del Uno (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'EL PODER DEL UNO'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // SEDE NACIONAL
                TextColumn::make('sede_nacional_bs')
                    ->label('Sede Nac. (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'SEDE NACIONAL'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sede_nacional_usd')
                    ->label('Sede Nac. (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'SEDE NACIONAL'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sede_nacional_cop')
                    ->label('Sede Nac. (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'SEDE NACIONAL'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // CONVENCIÓN DISTRITAL
                TextColumn::make('conv_distrital_bs')
                    ->label('Conv. Distrital (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN DISTRITAL'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conv_distrital_usd')
                    ->label('Conv. Distrital (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN DISTRITAL'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conv_distrital_cop')
                    ->label('Conv. Distrital (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN DISTRITAL'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // CONVENCIÓN REGIONAL
                TextColumn::make('conv_regional_bs')
                    ->label('Conv. Regional (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN REGIONAL'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conv_regional_usd')
                    ->label('Conv. Regional (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN REGIONAL'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conv_regional_cop')
                    ->label('Conv. Regional (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN REGIONAL'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // CONVENCIÓN NACIONAL
                TextColumn::make('conv_nacional_bs')
                    ->label('Conv. Nacional (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN NACIONAL'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conv_nacional_usd')
                    ->label('Conv. Nacional (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN NACIONAL'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('conv_nacional_cop')
                    ->label('Conv. Nacional (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CONVENCIÓN NACIONAL'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // ÚNICA SECTORIAL
                TextColumn::make('unica_sectorial_bs')
                    ->label('Única Sectorial (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'ÚNICA SECTORIAL'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('unica_sectorial_usd')
                    ->label('Única Sectorial (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'ÚNICA SECTORIAL'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('unica_sectorial_cop')
                    ->label('Única Sectorial (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'ÚNICA SECTORIAL'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // CAMPAMENTO DE RETIROS
                TextColumn::make('camp_retiros_bs')
                    ->label('Camp. Retiros (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CAMPAMENTO DE RETIROS'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('camp_retiros_usd')
                    ->label('Camp. Retiros (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CAMPAMENTO DE RETIROS'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('camp_retiros_cop')
                    ->label('Camp. Retiros (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'CAMPAMENTO DE RETIROS'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // ABISOP
                TextColumn::make('abisop_bs')
                    ->label('ABISOP (Bs)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'ABISOP'))
                        ->sum('amount_bs'))
                    ->money('VES')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('abisop_usd')
                    ->label('ABISOP (USD)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'ABISOP'))
                        ->sum('amount_usd'))
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('abisop_cop')
                    ->label('ABISOP (COP)')
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->whereHas('offeringCategory', fn ($q) => $q->where('name', 'ABISOP'))
                        ->sum('amount_cop'))
                    ->money('COP')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Columna resumen simplificada (opcional)
                TextColumn::make('offering_count')
                    ->label('Total Categorías')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->offeringItems()
                        ->distinct('offering_category_id')
                        ->count('offering_category_id'))
                    ->suffix(fn ($state) => $state === 1 ? ' categoría' : ' categorías')
                    ->color(fn ($state) => match(true) {
                        $state >= 7 => 'success',
                        $state >= 4 => 'warning',
                        $state >= 1 => 'info',
                        default => 'gray'
                    })
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ...self::getFilters(),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(false)
                    ->tooltip('Editar')
                    ->color('primary')
                    ->size('md')
                    ->visible(fn (OfferingReport $record) => Auth::user()->hasAnyRole([
                        'Tesorero Sectorial', 
                    ])),

                Tables\Actions\Action::make('descargarPDF')
                    ->label(false)
                    ->tooltip('Reporte de Ofrendas')
                    ->color('success')
                    ->size('md')
                    ->icon('heroicon-o-document')
                    ->action(function (OfferingReport $record) {
                        $service = new ReporteMensualOfrendasService();
                        $pdfPath = $service->fillReporteMensual($record);
                        
                        $cedula = $record->pastor->number_cedula ?? 'SIN_PASTOR';
                
                        return response()->download($pdfPath, "Reporte_Mensual_{$cedula}.pdf");
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s');
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
            'index' => Pages\ListOfferingReports::route('/'),
            'create' => Pages\CreateOfferingReport::route('/create'),
            'edit' => Pages\EditOfferingReport::route('/{record}/edit'),
        ];
    }

    /** @return Forms\Components\Component[] */
    public static function getDetailsFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\DatePicker::make('created_at')
                        ->label('Fecha de Creación')
                        ->default(now())
                        ->dehydrated()
                        ->disabled(),
                    
                    Forms\Components\Select::make('month')
                        ->label('Mes de Registro')
                        ->native(false)
                        ->options(function () {
                            $today = \Carbon\Carbon::now();
                            $months = [];
                    
                            for ($i = 1; $i <= 6; $i++) {
                                $month = $today->copy()->subMonthsNoOverflow($i);
                                $months[$month->format('Y-m')] = $month->translatedFormat('F Y');
                            }
                    
                            return array_reverse($months);
                        })
                        ->placeholder('Seleccione un mes')
                        ->required()
                        ->reactive()
                        ->disabled(function () {
                            return !Auth::user()->hasAnyRole([
                                'Tesorero Sectorial', 
                            ]);
                        }),

                    Forms\Components\Select::make('treasury_id')
                        ->label('Tesorería')
                        ->options(fn () => \App\Models\Treasury::where('level', auth()->user()->treasury_level)->pluck('name', 'id'))
                        ->default(fn () => \App\Models\Treasury::where('level', auth()->user()->treasury_level)->value('id'))
                        ->disabled()
                        ->required(),

                    // Campo para seleccionar el tipo de reporte
                    Forms\Components\Radio::make('report_type')
                        ->label('Tipo de Reporte')
                        ->options([
                            'pastor_con_iglesia' => 'Pastor con Iglesia',
                            'iglesia_sin_pastor' => 'Iglesia sin Pastor'
                        ])
                        ->default('pastor_con_iglesia')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            // Limpiar campos cuando cambia el tipo
                            $set('pastor_id', null);
                            $set('church_without_pastor', $state === 'iglesia_sin_pastor');
                        })
                        ->disabled(function () {
                            return !Auth::user()->hasAnyRole(['Tesorero Sectorial']);
                        }),

                    // Selector de pastor - Solo visible cuando es "Pastor con Iglesia"
                    Forms\Components\Select::make('pastor_id')
                        ->label('Seleccione un Pastor')
                        ->visible(fn (Get $get) => $get('report_type') === 'pastor_con_iglesia')
                        ->options(function (Get $get, ?OfferingReport $record) {
                            $user = auth()->user();
                            $selectedMonth = $get('month');
                            $isEditing = $record !== null;
                            
                            if (!method_exists($user, 'hasRole')) {
                                return [];
                            }

                            // Array para almacenar todas las opciones
                            $allOptions = [];

                            // Si estamos editando y hay un pastor asignado
                            if ($isEditing && $record->pastor_id) {
                                $currentPastor = Pastor::with(['sector', 'district'])->find($record->pastor_id);
                                
                                if ($currentPastor) {
                                    $label = $currentPastor->name . ' ' . $currentPastor->lastname;
                                    
                                    // Verificar según el rol del usuario si el pastor está fuera de su jurisdicción
                                    $isOutOfJurisdiction = match (true) {
                                        $user->hasRole('Tesorero Nacional') => false,
                                        $user->hasRole('Tesorero Regional') => $currentPastor->region_id !== $user->region_id,
                                        $user->hasRole('Supervisor Distrital') => $currentPastor->district_id !== $user->district_id,
                                        $user->hasRole('Tesorero Sectorial') => $currentPastor->sector_id !== $user->sector_id,
                                        default => true,
                                    };

                                    if ($isOutOfJurisdiction) {
                                        $newLocation = '';
                                        if ($currentPastor->sector) {
                                            $newLocation = $currentPastor->sector->name;
                                            if ($currentPastor->district) {
                                                $newLocation .= ', ' . $currentPastor->district->name;
                                            }
                                        }
                                        $label .= ' ⚠️ (TRASLADADO a ' . $newLocation . ')';
                                    }

                                    // Agregar el pastor actual como primera opción
                                    $allOptions['current'] = [
                                        $currentPastor->id => $label
                                    ];
                                }
                            }

                            // Query base para pastores activos según el rol del usuario
                            $query = match (true) {
                                $user->hasRole('Tesorero Nacional') => Pastor::query(),
                                $user->hasRole('Tesorero Regional') => Pastor::where('region_id', $user->region_id),
                                $user->hasRole('Supervisor Distrital') => Pastor::where('district_id', $user->district_id),
                                $user->hasRole('Tesorero Sectorial') => Pastor::where('sector_id', $user->sector_id),
                                default => Pastor::whereRaw('1 = 0'),
                            };

                            // Excluir pastores que ya tienen reporte para el mes seleccionado
                            if ($selectedMonth) {
                                $query->whereDoesntHave('offeringReports', function ($q) use ($selectedMonth, $record) {
                                    $q->where('month', $selectedMonth);
                                    if ($record) {
                                        $q->where('id', '!=', $record->id);
                                    }
                                });
                            }

                            // Obtener pastores activos
                            $activePastors = $query
                                ->with(['sector', 'district'])
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(function ($pastor) {
                                    return [$pastor->id => $pastor->name . ' ' . $pastor->lastname];
                                })
                                ->toArray();

                            // Si hay pastores activos, agregarlos bajo una categoría
                            if (!empty($activePastors)) {
                                $allOptions['Pastores Activos en el Sector'] = $activePastors;
                            }

                            // Si estamos editando, agregar pastores históricos del sector
                            if ($isEditing && $selectedMonth) {
                                $historicalPastors = Pastor::whereHas('offeringReports', function ($q) use ($selectedMonth, $user) {
                                        $q->where('month', '<', $selectedMonth);
                                        
                                        // Filtrar por jurisdicción según el rol
                                        if ($user->hasRole('Tesorero Regional')) {
                                            $q->where('region_id', $user->region_id);
                                        } elseif ($user->hasRole('Supervisor Distrital')) {
                                            $q->where('district_id', $user->district_id);
                                        } elseif ($user->hasRole('Tesorero Sectorial')) {
                                            $q->where('sector_id', $user->sector_id);
                                        }
                                    })
                                    ->whereDoesntHave('offeringReports', function ($q) use ($selectedMonth) {
                                        $q->where('month', $selectedMonth);
                                    })
                                    ->where('id', '!=', $record->pastor_id ?? 0)
                                    ->orderBy('name')
                                    ->get();

                                if ($historicalPastors->count() > 0) {
                                    $historicalOptions = $historicalPastors->mapWithKeys(function ($pastor) {
                                        return [$pastor->id => $pastor->name . ' ' . $pastor->lastname . ' 📋 (Histórico en el sector)'];
                                    })->toArray();
                                    
                                    $allOptions['Pastores Históricos del Sector'] = $historicalOptions;
                                }
                            }

                            // Si solo hay una categoría (current), retornar solo esas opciones
                            if (count($allOptions) === 1 && isset($allOptions['current'])) {
                                return $allOptions['current'];
                            }

                            return $allOptions;
                        })
                        ->searchable()
                        ->required(fn (Get $get) => $get('report_type') === 'pastor_con_iglesia')
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $pastor = Pastor::with(['ministry', 'type'])->find($state);

                                if ($pastor) {
                                    $set('pastor_number_cedula', $pastor->number_cedula);
                                    $set('pastor_code', $pastor->ministry?->code_pastor);
                                    $set('pastor_type_name', $pastor->type?->name);
                                    $set('pastor_phone_mobile', $pastor->phone_mobile);
                                    $set('phone_house', $pastor->phone_house);
                                    $set('email', $pastor->email);
                                    $set('pastor_type_id', $pastor->type?->id);
                                }
                            } else {
                                // Limpiar campos si no hay selección
                                $set('pastor_number_cedula', null);
                                $set('pastor_code', null);
                                $set('pastor_type_name', null);
                                $set('pastor_phone_mobile', null);
                                $set('phone_house', null);
                                $set('email', null);
                                $set('pastor_type_id', null);
                            }
                        })
                        ->helperText(function (Get $get, ?OfferingReport $record) {
                            if (!$get('month')) {
                                return 'Seleccione primero un mes';
                            }
                            
                            if ($record && $record->pastor && $record->pastor->sector_id !== auth()->user()->sector_id) {
                                return '⚠️ Este pastor fue trasladado pero puede editar el reporte histórico';
                            }
                            
                            return 'Incluye pastores activos e históricos del sector';
                        })
                        ->hint(function (?OfferingReport $record) {
                            if ($record && $record->created_at) {
                                return 'Registro creado: ' . $record->created_at->format('d/m/Y');
                            }
                            return null;
                        }),

                    // Selector de pastor representante - Solo para "Iglesia sin Pastor"
                    Forms\Components\Select::make('pastor_id')
                        ->label('Seleccione el Pastor Representante')
                        ->visible(fn (Get $get) => $get('report_type') === 'iglesia_sin_pastor')
                        ->options(function (Get $get) {
                            $user = auth()->user();
                            
                            if (!method_exists($user, 'hasRole')) {
                                return [];
                            }

                            // Query base para todos los pastores del sector (sin exclusiones)
                            $query = match (true) {
                                $user->hasRole('Tesorero Nacional') => Pastor::query(),
                                $user->hasRole('Tesorero Regional') => Pastor::where('region_id', $user->region_id),
                                $user->hasRole('Supervisor Distrital') => Pastor::where('district_id', $user->district_id),
                                $user->hasRole('Tesorero Sectorial') => Pastor::where('sector_id', $user->sector_id),
                                default => Pastor::whereRaw('1 = 0'),
                            };

                            // Obtener todos los pastores del sector
                            return $query
                                ->with(['sector', 'district'])
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(function ($pastor) {
                                    return [$pastor->id => $pastor->name . ' ' . $pastor->lastname];
                                })
                                ->toArray();
                        })
                        ->searchable()
                        ->required(fn (Get $get) => $get('report_type') === 'iglesia_sin_pastor')
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $pastor = Pastor::with(['ministry', 'type'])->find($state);

                                if ($pastor) {
                                    $set('pastor_number_cedula', $pastor->number_cedula);
                                    $set('pastor_code', $pastor->ministry?->code_pastor);
                                    $set('pastor_type_name', $pastor->type?->name);
                                    $set('pastor_phone_mobile', $pastor->phone_mobile);
                                    $set('phone_house', $pastor->phone_house);
                                    $set('email', $pastor->email);
                                    $set('pastor_type_id', $pastor->type?->id);
                                }
                            }
                        })
                        ->helperText('Seleccione el pastor responsable de supervisar esta iglesia'),



                    // Campo oculto para marcar iglesias sin pastor
                    Forms\Components\Hidden::make('church_without_pastor')
                        ->default(false)
                        ->dehydrated(),

                    // Textarea para notas históricas
                    Forms\Components\Textarea::make('historical_note')
                        ->label('Nota histórica')
                        ->placeholder('Ej: Pastor trasladado al Sector X en febrero 2025. Este reporte corresponde a su gestión en enero.')
                        ->visible(function (?OfferingReport $record) {
                            if (!$record || !$record->pastor) return false;
                            
                            // Mostrar si el pastor ya no está en el mismo sector que el registro
                            return $record->pastor->sector_id !== $record->sector_id;
                        })
                        ->helperText('Documente cualquier cambio posterior al registro')
                        ->rows(2)
                        ->maxLength(500)
                        ->dehydrated(),
                ])
                ->columnSpan(3),

            Forms\Components\Grid::make(4)
                ->schema([
                    TextInput::make('pastor_number_cedula')
                        ->label('Número de Cédula')
                        ->disabled(),
                    TextInput::make('pastor_code')
                        ->label('Código Pastoral')
                        ->disabled(),
                    TextInput::make('pastor_type_name')
                        ->label('Tipo de Pastor')
                        ->disabled(),
                    TextInput::make('pastor_phone_mobile')
                        ->label('Teléfono Móvil')
                        ->disabled(),
                    TextInput::make('phone_house')
                        ->label('Teléfono Fijo')
                        ->disabled(),
                    TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->disabled(),
                    Forms\Components\Hidden::make('pastor_type_id')->dehydrated(),
                ])
                ->columns(4),

            Forms\Components\Textarea::make('remarks')
                ->label('Observaciones')
                ->placeholder('Añadir notas o comentarios')
                ->maxLength(250)
                ->rows(2)
                ->columnSpan('full')
                ->disabled(function () {
                    return !Auth::user()->hasAnyRole([
                        'Tesorero Sectorial', 
                    ]);
                }),
            
                    Forms\Components\Grid::make(3)
                        ->schema([
                            // Campo Select para elegir la iglesia
                            Forms\Components\Select::make('church_id')
                                ->label('Seleccione una Iglesia')
                                ->options(function () {
                                    $user = auth()->user();
                                    if (method_exists($user, 'hasRole')) {
                                        return match (true) {
                                            $user->hasRole('Tesorero Nacional') => Church::orderBy('name') // Ordenar por nombre de forma ascendente
                                                ->pluck('name', 'id'),
                                            $user->hasRole('Tesorero Regional') => Church::where('region_id', $user->region_id)
                                                ->orderBy('name') // Ordenar por nombre de forma ascendente
                                                ->pluck('name', 'id'),
                                            $user->hasRole('Supervisor Distrital') => Church::where('district_id', $user->district_id)
                                                ->orderBy('name') // Ordenar por nombre de forma ascendente
                                                ->pluck('name', 'id'),
                                            $user->hasRole('Tesorero Sectorial') => Church::where('sector_id', $user->sector_id)
                                                ->orderBy('name') // Ordenar por nombre de forma ascendente
                                                ->pluck('name', 'id'),
                                            default => [],
                                        };
                                    }
                                    return [];
                                })
                                ->searchable()
                                //->columnSpan(2)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Buscar la iglesia seleccionada con las relaciones cargadas
                                    $church = Church::with(['region', 'district', 'sector'])->find($state);

                                    if ($church) {
                                        // Rellenar automáticamente los campos relacionados con la iglesia
                                        $set('code_church', $church->code_church);
                                        $set('church_address', $church->address);
                                        // Mostrar nombres de Región, Distrito y Sector
                                        $set('region_name', $church->region?->name);
                                        $set('district_name', $church->district?->name);
                                        $set('sector_name', $church->sector?->name);

                                        // Guardar valores reales en los campos ocultos
                                        $set('region_id', $church->region?->id);
                                        $set('district_id', $church->district?->id);
                                        $set('sector_id', $church->sector?->id);
                                    } else {
                                        // Limpiar los campos si no se encuentra la iglesia
                                        $set('code_church', null);
                                        $set('church_address', null);
                                        $set('region_name', null);
                                        $set('district_name', null);
                                        $set('sector_name', null);
                                        $set('region_id', null);
                                        $set('district_id', null);
                                        $set('sector_id', null);
                                    }
                                })
                                ->disabled(function () {
                                    // Deshabilitar el campo si el usuario no tiene los roles permitidos
                                    return !Auth::user()->hasAnyRole([
                                        'Tesorero Sectorial', 
                                    ]);
                                }),

                            // Otros campos del formulario
                            Forms\Components\TextInput::make('code_church')
                                ->label('Código de Iglesia')
                                ->disabled(),
                                //->dehydrated(),
                            Forms\Components\TextInput::make('church_address')
                                ->label('Dirección de Iglesia')
                                ->columnSpan('full')
                                ->disabled(),
                                //->dehydrated(),
                            Forms\Components\TextInput::make('region_name')
                                ->label('Región')
                                ->disabled(),
                            Forms\Components\TextInput::make('district_name')
                                ->label('Distrito')
                                ->disabled(),
                            Forms\Components\TextInput::make('sector_name')
                                ->label('Sector')
                                ->disabled(),
                            // **Campos ocultos para enviar valores al backend**
                            // Campos ocultos con valores por defecto tomados del usuario --------------------------------
                            Forms\Components\Hidden::make('region_id')
                                ->dehydrated()
                                ->default(fn () => auth()->user()->region_id),

                            Forms\Components\Hidden::make('district_id')
                                ->dehydrated()
                                ->default(fn () => auth()->user()->district_id),

                            Forms\Components\Hidden::make('sector_id')
                                ->dehydrated()
                                ->default(fn () => auth()->user()->sector_id)
                            
                        ])
                        ->columns(3),
                

            // Sección de Tasas de Cambio (sin cambios)
            Forms\Components\Section::make('Tasas de Cambio')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('usd_rate')
                                ->label('Tasa USD (Bs/USD)')
                                ->numeric()
                                ->step(0.0001)
                                ->default(function () {
                                    $usdRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'USD')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    $rate = $usdRate ? (float) $usdRate->rate_to_bs : 50.0000;
                                    return number_format($rate, 4, '.', '');
                                })
                                ->dehydrateStateUsing(function ($state) {
                                    if (!$state) return 0;
                                    // Reemplazar coma por punto para manejar ambos formatos
                                    $cleaned = str_replace(',', '.', $state);
                                    return (float) $cleaned;
                                })
                                ->formatStateUsing(function ($state) {
                                    if (!$state) {
                                        // Si no hay estado, buscar el valor más reciente
                                        $usdRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                        ->whereNull('month')
                                                                        ->where('currency', 'USD')
                                                                        ->latest('updated_at')
                                                                        ->first();
                                        $rate = $usdRate ? (float) $usdRate->rate_to_bs : 50.0000;
                                        return number_format($rate, 4, '.', '');
                                    }
                                    return number_format((float) $state, 4, '.', '');
                                })
                                ->dehydrated()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Forzar recálculo de totales cuando cambie la tasa
                                    if ($get('offering_items')) {
                                        self::recalculateAllTotals($set, $get);
                                    }
                                })
                                ->disabled(fn () => ! auth()->user()?->hasAnyRole([
                                    'Administrador',
                                    'Tesorero Nacional',
                                    'Tesorero Sectorial',
                                ]))
                                ->helperText(function () {
                                    $usdRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'USD')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    if ($usdRate) {
                                        $lastUpdate = $usdRate->updated_at;
                                        $isToday = $lastUpdate->isToday();
                                        $timeAgo = $lastUpdate->diffForHumans();
                                        
                                        $status = $isToday 
                                            ? "✅ Actualizada hoy ({$lastUpdate->format('H:i')})"
                                            : "⚠️ Última actualización: {$timeAgo}";
                                        
                                        return "Tasa oficial automática: {$status}";
                                    }
                                    
                                    return "❌ No hay tasa oficial disponible";
                                })
                                ->hint(function () {
                                    $usdRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'USD')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    return $usdRate && $usdRate->updated_at->isToday() 
                                        ? 'Actualizada automáticamente'
                                        : 'Verificar actualización';
                                })
                                ->hintColor(function () {
                                    $usdRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'USD')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    return $usdRate && $usdRate->updated_at->isToday() ? 'success' : 'warning';
                                }),

                            Forms\Components\TextInput::make('cop_rate')
                                ->label('Tasa COP (COP/Bs)')
                                ->numeric()
                                ->step(0.01)
                                ->default(function () {
                                    $copRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'COP')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    $rate = $copRate ? (float) $copRate->rate_to_bs : 50.00;
                                    return number_format($rate, 2, '.', '');
                                })
                                ->dehydrateStateUsing(function ($state) {
                                    if (!$state) return 0;
                                    // Reemplazar coma por punto para manejar ambos formatos
                                    $cleaned = str_replace(',', '.', $state);
                                    return (float) $cleaned;
                                })
                                ->formatStateUsing(function ($state) {
                                    if (!$state) {
                                        // Si no hay estado, buscar el valor más reciente
                                        $copRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                        ->whereNull('month')
                                                                        ->where('currency', 'COP')
                                                                        ->latest('updated_at')
                                                                        ->first();
                                        $rate = $copRate ? (float) $copRate->rate_to_bs : 50.00;
                                        return number_format($rate, 2, '.', '');
                                    }
                                    return number_format((float) $state, 2, '.', '');
                                })
                                ->dehydrated()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Forzar recálculo de totales cuando cambie la tasa
                                    if ($get('offering_items')) {
                                        self::recalculateAllTotals($set, $get);
                                    }
                                })
                                ->disabled(fn () => ! auth()->user()?->hasAnyRole([
                                    'Administrador',
                                    'Tesorero Nacional',
                                    'Tesorero Sectorial',
                                ]))
                                ->helperText(function () {
                                    $copRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'COP')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    if ($copRate) {
                                        $lastUpdate = $copRate->updated_at;
                                        $isToday = $lastUpdate->isToday();
                                        $timeAgo = $lastUpdate->diffForHumans();
                                        
                                        $status = $isToday 
                                            ? "✅ Actualizada hoy ({$lastUpdate->format('H:i')})"
                                            : "⚠️ Última actualización: {$timeAgo}";
                                        
                                        return "Tasa calculada automáticamente: {$status}";
                                    }
                                    
                                    return "❌ No hay tasa calculada disponible";
                                })
                                ->hint(function () {
                                    $copRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'COP')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    return $copRate && $copRate->updated_at->isToday() 
                                        ? 'Calculada automáticamente'
                                        : 'Verificar cálculo';
                                })
                                ->hintColor(function () {
                                    $copRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'COP')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    return $copRate && $copRate->updated_at->isToday() ? 'success' : 'warning';
                                }),
                        ]),
                    
                    // Sección informativa adicional
                    Forms\Components\Section::make('Información de Tasas')
                        ->description('Las tasas se actualizan automáticamente desde fuentes oficiales')
                        ->schema([
                            Forms\Components\Placeholder::make('exchange_info')
                                ->label('')
                                ->content(function () {
                                    $usdRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'USD')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    $copRate = \App\Models\ExchangeRate::whereNull('sector_id')
                                                                    ->whereNull('month')
                                                                    ->where('currency', 'COP')
                                                                    ->latest('updated_at')
                                                                    ->first();
                                    
                                    $info = '<div class="space-y-2 text-sm">';
                                    
                                    if ($usdRate) {
                                        $usdStatus = $usdRate->updated_at->isToday() ? '🟢' : '🟡';
                                        $info .= "<div><strong>{$usdStatus} 1 USD =</strong> " . number_format($usdRate->rate_to_bs, 4, ',', '.') . " Bs (fuente: DolarAPI)</div>";
                                    }
                                    
                                    if ($copRate) {
                                        $copStatus = $copRate->updated_at->isToday() ? '🟢' : '🟡';
                                        $info .= "<div><strong>{$copStatus} " . number_format($copRate->rate_to_bs, 2, ',', '.') . " COP =</strong> 1 Bs (calculada automáticamente)</div>";
                                    }
                                    
                                    if ($usdRate && $copRate && $usdRate->updated_at->isToday()) {
                                        $info .= '<div class="mt-2 text-green-600"><strong>✅ Tasas actualizadas hoy</strong></div>';
                                    } else {
                                        $info .= '<div class="mt-2 text-yellow-600"><strong>⚠️ Verificar próxima actualización automática</strong></div>';
                                    }
                                    
                                    $info .= '</div>';
                                    
                                    return new \Illuminate\Support\HtmlString($info);
                                })
                        ])
                        ->collapsible()
                        ->collapsed(),
                ])
                ->columns(2),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('offering_items')
            ->relationship('offeringItems')
            ->label('Items')
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('offering_category_id')
                            ->label('Tipo de Ofrenda')
                            ->native(false)
                            ->options(function (Get $get) {
                                // Obtener todas las categorías disponibles
                                $allCategories = \App\Models\OfferingCategory::pluck('name', 'id');
                                
                                // Obtener las categorías ya seleccionadas en este formulario
                                $items = $get('../../offering_items') ?? [];
                                $selectedCategories = collect($items)
                                    ->pluck('offering_category_id')
                                    ->filter()
                                    ->toArray();
                                
                                // Obtener la categoría actual (en caso de edición)
                                $currentCategory = $get('offering_category_id');
                                
                                // Filtrar las categorías disponibles
                                return $allCategories->filter(function ($name, $id) use ($selectedCategories, $currentCategory) {
                                    // Siempre mostrar la categoría actual
                                    if ($id == $currentCategory) {
                                        return true;
                                    }
                                    // No mostrar categorías ya seleccionadas
                                    return !in_array($id, $selectedCategories);
                                });
                            })
                            ->required()
                            ->reactive()
                            ->disabled(function () {
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            })
                            ->helperText('Solo se muestran categorías no seleccionadas'),

                        Select::make('bank_transaction_id')
                            ->label('Tipo de Transacción')
                            ->native(false)
                            ->placeholder('Seleccione...')
                            ->options(BankTransaction::pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                $showFields = in_array($state, [1, 2]);
                                $set('show_fields', $showFields);
                            })
                            ->disabled(function () {
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),

                        Hidden::make('show_fields')
                            ->default(false),

                        Select::make('bank_id')
                            ->label('Banco')
                            ->placeholder('Seleccione un banco...')
                            ->options(Bank::pluck('name', 'id'))
                            ->visible(fn (callable $get) => $get('show_fields'))
                            ->nullable()
                            ->disabled(function () {
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),

                        DatePicker::make('transaction_date')
                            ->label('Fecha de la Transacción')
                            ->visible(fn (callable $get) => $get('show_fields'))
                            ->nullable()
                            ->maxDate(now())
                            ->disabled(function () {
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),

                        TextInput::make('transaction_number')
                            ->label('Número de Transacción')
                            ->visible(fn (callable $get) => $get('show_fields'))
                            ->nullable()
                            ->disabled(function () {
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            }),
                    ]),

                Grid::make(4)
                    ->schema([
                        TextInput::make('amount_bs')
                            ->label('Monto en Bs')
                            ->default('0,00')
                            ->prefix('Bs')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Limpiar el valor antes de calcular
                                $cleanValue = self::cleanMoneyValue($state);
                                $set('amount_bs', self::formatMoney($cleanValue));
                                
                                self::calculateSubtotal($set, $get);
                                self::calculateGlobalTotals($set, $get);
                            })
                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                            ->disabled(function () {
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            })
                            ->helperText('Formato: 1.234.567,89')
                            ->extraAttributes([
                                'x-data' => '{
                                    init() {
                                        this.$el.addEventListener("input", (e) => {
                                            let value = e.target.value;
                                            let cursorPos = e.target.selectionStart;
                                            let oldLength = value.length;
                                            
                                            // Remover todo excepto números y coma
                                            value = value.replace(/[^0-9,]/g, "");
                                            
                                            // Solo permitir una coma
                                            let parts = value.split(",");
                                            if (parts.length > 2) {
                                                value = parts[0] + "," + parts.slice(1).join("");
                                            }
                                            
                                            // Limitar decimales a 2
                                            if (parts.length === 2 && parts[1].length > 2) {
                                                parts[1] = parts[1].substring(0, 2);
                                                value = parts.join(",");
                                            }
                                            
                                            // Formatear parte entera con puntos
                                            if (parts[0]) {
                                                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                            }
                                            
                                            e.target.value = parts.join(",");
                                            
                                            // Ajustar posición del cursor
                                            let newLength = e.target.value.length;
                                            let diff = newLength - oldLength;
                                            e.target.setSelectionRange(cursorPos + diff, cursorPos + diff);
                                        });
                                    }
                                }'
                            ]),

                        TextInput::make('amount_usd')
                            ->label('Monto en USD')
                            ->default('0,00')
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Limpiar el valor antes de calcular
                                $cleanValue = self::cleanMoneyValue($state);
                                $set('amount_usd', self::formatMoney($cleanValue));
                                
                                self::calculateSubtotal($set, $get);
                                self::calculateGlobalTotals($set, $get);
                            })
                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                            ->disabled(function () {
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            })
                            ->helperText('Formato: 1.234.567,89')
                            ->extraAttributes([
                                'x-data' => '{
                                    init() {
                                        this.$el.addEventListener("input", (e) => {
                                            let value = e.target.value;
                                            let cursorPos = e.target.selectionStart;
                                            let oldLength = value.length;
                                            
                                            // Remover todo excepto números y coma
                                            value = value.replace(/[^0-9,]/g, "");
                                            
                                            // Solo permitir una coma
                                            let parts = value.split(",");
                                            if (parts.length > 2) {
                                                value = parts[0] + "," + parts.slice(1).join("");
                                            }
                                            
                                            // Limitar decimales a 2
                                            if (parts.length === 2 && parts[1].length > 2) {
                                                parts[1] = parts[1].substring(0, 2);
                                                value = parts.join(",");
                                            }
                                            
                                            // Formatear parte entera con puntos
                                            if (parts[0]) {
                                                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                            }
                                            
                                            e.target.value = parts.join(",");
                                            
                                            // Ajustar posición del cursor
                                            let newLength = e.target.value.length;
                                            let diff = newLength - oldLength;
                                            e.target.setSelectionRange(cursorPos + diff, cursorPos + diff);
                                        });
                                    }
                                }'
                            ]),

                        TextInput::make('amount_cop')
                            ->label('Monto en COP')
                            ->default('0,00')
                            ->prefix('COP')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Limpiar el valor antes de calcular
                                $cleanValue = self::cleanMoneyValue($state);
                                $set('amount_cop', self::formatMoney($cleanValue));
                                
                                self::calculateSubtotal($set, $get);
                                self::calculateGlobalTotals($set, $get);
                            })
                            ->dehydrateStateUsing(fn ($state) => self::cleanMoneyValue($state))
                            ->formatStateUsing(fn ($state) => self::formatMoney($state))
                            ->disabled(function () {
                                return !Auth::user()->hasAnyRole([
                                    'Tesorero Sectorial', 
                                ]);
                            })
                            ->helperText('Formato: 1.234.567,89')
                            ->extraAttributes([
                                'x-data' => '{
                                    init() {
                                        this.$el.addEventListener("input", (e) => {
                                            let value = e.target.value;
                                            let cursorPos = e.target.selectionStart;
                                            let oldLength = value.length;
                                            
                                            // Remover todo excepto números y coma
                                            value = value.replace(/[^0-9,]/g, "");
                                            
                                            // Solo permitir una coma
                                            let parts = value.split(",");
                                            if (parts.length > 2) {
                                                value = parts[0] + "," + parts.slice(1).join("");
                                            }
                                            
                                            // Limitar decimales a 2
                                            if (parts.length === 2 && parts[1].length > 2) {
                                                parts[1] = parts[1].substring(0, 2);
                                                value = parts.join(",");
                                            }
                                            
                                            // Formatear parte entera con puntos
                                            if (parts[0]) {
                                                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                            }
                                            
                                            e.target.value = parts.join(",");
                                            
                                            // Ajustar posición del cursor
                                            let newLength = e.target.value.length;
                                            let diff = newLength - oldLength;
                                            e.target.setSelectionRange(cursorPos + diff, cursorPos + diff);
                                        });
                                    }
                                }'
                            ]),

                        TextInput::make('subtotal_bs')
                            ->label('Subtotal en Bs')
                            ->default('0,00')
                            ->disabled()
                            ->dehydrated()
                            ->prefix('Bs')
                            ->formatStateUsing(fn ($state) => self::formatMoney($state)),
                    ]),
            ])
            ->defaultItems(1)
            ->createItemButtonLabel('Agregar Ofrenda')
            ->deleteAction(
                fn (Action $action) => $action->requiresConfirmation(),
            )
            ->disableItemCreation(fn() => !Auth::user()->hasRole('Tesorero Sectorial'))
            ->disableItemDeletion(fn() => !Auth::user()->hasRole('Tesorero Sectorial'))
            ->columns(1);
    }


    /**
     * Recalcula todos los subtotales cuando cambia una tasa de cambio
     */
    public static function recalculateAllTotals(callable $set, callable $get): void
    {
        $items = $get('offering_items') ?? [];
        
        // Para las tasas, solo cambiar coma por punto
        $usdRate = (float) str_replace(',', '.', $get('usd_rate') ?? 0);
        $copRate = (float) str_replace(',', '.', $get('cop_rate') ?? 0);
        
        // Prevenir división por cero
        $copRate = $copRate > 0 ? $copRate : 1;
        
        // Variables para acumular totales
        $totalBs = 0;
        $totalUsd = 0;
        $totalCop = 0;
        
        // Recalcular cada subtotal individual y acumular totales
        foreach ($items as $index => $item) {
            $amountBs = self::cleanMoneyValue($item['amount_bs'] ?? 0);
            $amountUsd = self::cleanMoneyValue($item['amount_usd'] ?? 0);
            $amountCop = self::cleanMoneyValue($item['amount_cop'] ?? 0);
            
            // Calcular subtotal del item
            $subtotalBs = $amountBs + ($amountUsd * $usdRate) + ($amountCop / $copRate);
            
            // Actualizar el subtotal del item en el formulario
            $set("offering_items.{$index}.subtotal_bs", round($subtotalBs, 2));
            
            // Acumular para los totales globales
            $totalBs += $amountBs;
            $totalUsd += $amountUsd;
            $totalCop += $amountCop;
        }
        
        // Calcular conversiones
        $totalUsdToBs = $totalUsd * $usdRate;
        $totalCopToBs = $totalCop / $copRate;
        $grandTotalBs = $totalBs + $totalUsdToBs + $totalCopToBs;
        
        // Actualizar los totales globales
        $set('total_bs', round($totalBs, 2));
        $set('total_usd', round($totalUsd, 2));
        $set('total_cop', round($totalCop, 2));
        $set('total_usd_to_bs', round($totalUsdToBs, 2));
        $set('total_cop_to_bs', round($totalCopToBs, 2));
        $set('grand_total_bs', round($grandTotalBs, 2));
    }
    
    /**
     * Limpia el valor monetario y lo convierte a float
     */
    public static function cleanMoneyValue($value): float
    {
        if (!$value) return 0;
        
        // Si ya es numérico, devolverlo
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Convertir a string si no lo es
        $value = (string) $value;
        
        // Remover espacios
        $value = trim($value);
        
        // Remover todos los puntos (separador de miles)
        $value = str_replace('.', '', $value);
        
        // Cambiar coma por punto (separador decimal)
        $value = str_replace(',', '.', $value);
        
        // Convertir a float
        return (float) $value;
    }

    /**
     * Formatea un número como moneda
     */
    public static function formatMoney($value): string
    {
        if (!is_numeric($value)) {
            $value = self::cleanMoneyValue($value);
        }
        
        return number_format((float) $value, 2, ',', '.');
    }

    public static function calculateSubtotal(callable $set, callable $get)
    {
        // Función helper para limpiar y convertir valores
        $cleanValue = function($value) {
            if (!$value) return 0;
            
            if (is_string($value)) {
                // Primero remover puntos (separador de miles)
                $value = str_replace('.', '', $value);
                // Luego cambiar coma por punto (separador decimal)
                $value = str_replace(',', '.', $value);
            }
            
            return (float) $value;
        };

        $amountBs = $cleanValue($get('amount_bs'));
        $amountUsd = $cleanValue($get('amount_usd'));
        $amountCop = $cleanValue($get('amount_cop'));

        // Para las tasas, solo necesitamos cambiar coma por punto
        $usdRate = (float) str_replace(',', '.', $get('../../usd_rate') ?? 0);
        $copRate = (float) str_replace(',', '.', $get('../../cop_rate') ?? 0);

        // Prevenir división por cero
        $copRate = $copRate > 0 ? $copRate : 1;

        // Cálculo correcto: COP / tasa
        $subtotalBs = $amountBs + ($amountUsd * $usdRate) + ($amountCop / $copRate);
        
        $set('subtotal_bs', round($subtotalBs, 2));
    }

    protected static function calculateGlobalTotals(callable $set, callable $get): void
    {
        $items = $get('../../offering_items') ?? [];

        $totalBs = 0;
        $totalUsd = 0;
        $totalCop = 0;

        // Para las tasas, solo cambiar coma por punto (sin usar cleanMoneyValue)
        $usdRate = (float) str_replace(',', '.', $get('../../usd_rate') ?? 0);
        $copRate = (float) str_replace(',', '.', $get('../../cop_rate') ?? 0);
        
        // Prevenir división por cero
        $copRate = $copRate > 0 ? $copRate : 1;

        // Sumar los montos de cada item
        foreach ($items as $item) {
            $totalBs += self::cleanMoneyValue($item['amount_bs'] ?? 0);
            $totalUsd += self::cleanMoneyValue($item['amount_usd'] ?? 0);
            $totalCop += self::cleanMoneyValue($item['amount_cop'] ?? 0);
        }

        // Calcular las conversiones
        $totalUsdToBs = $totalUsd * $usdRate;
        $totalCopToBs = $totalCop / $copRate;

        // Calcular el gran total
        $grandTotalBs = $totalBs + $totalUsdToBs + $totalCopToBs;

        // Establecer los valores como números (sin formato)
        // El formatStateUsing en los campos se encargará de mostrarlos formateados
        $set('../../total_bs', round($totalBs, 2));
        $set('../../total_usd', round($totalUsd, 2));
        $set('../../total_cop', round($totalCop, 2));
        $set('../../total_usd_to_bs', round($totalUsdToBs, 2));
        $set('../../total_cop_to_bs', round($totalCopToBs, 2));
        $set('../../grand_total_bs', round($grandTotalBs, 2));
    }

    public static function getFilters(): array
    {
        $user = auth()->user();

        // Si el usuario es Pastor o Directivo Nacional, no mostrar ningún filtro
        if ($user->hasAnyRole(['Pastor', 'Directivo Nacional'])) {
            return [];
        }

        return [
            SelectFilter::make('month')
                ->label('Filtrar por Mes')
                ->options(function () {
                    $months = [];
                    $today = \Carbon\Carbon::now();

                    $months[$today->format('Y-m')] = $today->translatedFormat('F Y') . ' (Actual)';

                    for ($i = 1; $i <= 6; $i++) {
                        $month = $today->copy()->subMonthsNoOverflow($i);
                        $monthKey = $month->format('Y-m');
                        $monthLabel = $month->translatedFormat('F Y');

                        if ($i === 1) {
                            $monthLabel .= ' (Anterior)';
                        }

                        $months[$monthKey] = $monthLabel;
                    }

                    return $months;
                })
                ->searchable()
                ->placeholder('Seleccione un mes...')
                ->default(fn () => \Carbon\Carbon::now()->subMonth()->format('Y-m'))
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $month): Builder => $query->where('month', $month)
                    );
                })
                ->indicateUsing(function (array $data): ?string {
                    if (!$data['value']) {
                        return null;
                    }

                    $month = \Carbon\Carbon::createFromFormat('Y-m', $data['value']);
                    return 'Mes: ' . $month->translatedFormat('F Y');
                }),

            SelectFilter::make('status')
                ->label('Estado')
                ->options([
                    'pendiente' => 'Pendiente',
                    'aprobado' => 'Aprobado',
                ])
                ->searchable(),

            SelectFilter::make('pastor_type')
                ->label('Tipo de Reporte')
                ->options([
                    'con_pastor' => 'Con Pastor',
                    'sin_pastor' => 'Sin Pastor',
                    'trasladado' => 'Pastor Trasladado',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when($data['value'], function ($query, $value) {
                        return match ($value) {
                            'con_pastor' => $query->whereNotNull('pastor_id')->where('church_without_pastor', false),
                            'sin_pastor' => $query->where('church_without_pastor', true)->orWhereNull('pastor_id'),
                            'trasladado' => $query->whereHas('pastor', function ($q) {
                                $q->whereColumn('pastors.sector_id', '!=', 'offering_reports.sector_id');
                            }),
                            default => $query,
                        };
                    });
                }),

            SelectFilter::make('pastor_id')
                ->label('Pastor')
                ->relationship('pastor', 'name', fn ($query) => $query->orderBy('name'))
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' ' . $record->lastname)
                ->searchable()
                ->preload(),

            SelectFilter::make('church_id')
                ->label('Iglesia')
                ->relationship('church', 'name')
                ->searchable()
                ->preload(),

            ...self::getUbicacionGeograficaFilters(),
        ];
    }
    
}