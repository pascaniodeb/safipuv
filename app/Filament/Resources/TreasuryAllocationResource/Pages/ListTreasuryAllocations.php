<?php

namespace App\Filament\Resources\TreasuryAllocationResource\Pages;

use App\Filament\Resources\TreasuryAllocationResource;
use App\Models\ExchangeRate;
use Livewire\Livewire;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\TopChurchesOfferingsReportService;
use Illuminate\Support\Facades\Auth;
use App\Models\TreasuryAllocation;
use App\Models\OfferingReport;
use Filament\Forms\Components;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTreasuryAllocations extends ListRecords
{
    protected static string $resource = TreasuryAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Action::make('generar_pdf_sector')
                ->label('PDF Deducción Sectorial')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->visible(fn () => Auth::user()?->hasRole('Tesorero Sectorial'))
                ->form([
                    Forms\Components\Select::make('month')
                        ->label('Seleccione el Mes')
                        ->options(
                            \App\Models\TreasuryAllocation::select('month')
                                ->distinct()
                                ->orderByDesc('month')
                                ->pluck('month')
                                ->filter(fn ($mes) => \Carbon\Carbon::parse($mes . '-01')->year === now()->year)
                                ->mapWithKeys(fn ($mes) => [
                                    $mes => \Carbon\Carbon::parse($mes . '-01')->translatedFormat('F/Y'),
                                ])
                                ->toArray()
                        )
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $sectorId = Auth::user()?->sector_id;

                            $usd = ExchangeRate::where('month', $state)
                                ->where('currency', 'USD')
                                ->where('operation', 'D') // Tasa oficial para deducción
                                ->where('sector_id', $sectorId)
                                ->value('rate_to_bs');

                            $cop = ExchangeRate::where('month', $state)
                                ->where('currency', 'COP')
                                ->where('operation', 'D')
                                ->where('sector_id', $sectorId)
                                ->value('rate_to_bs');

                            if ($usd) {
                                $set('usd_rate', $usd);
                                $set('usd_locked', true);
                            } else {
                                $set('usd_rate', null);
                                $set('usd_locked', false);
                            }

                            if ($cop) {
                                $set('cop_rate', $cop);
                                $set('cop_locked', true);
                            } else {
                                $set('cop_rate', null);
                                $set('cop_locked', false);
                            }
                        }),

                    Forms\Components\TextInput::make('usd_rate')
                        ->label('Tasa USD → Bs')
                        ->numeric()
                        ->required()
                        ->disabled(fn (callable $get) => $get('usd_locked'))
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('cop_rate')
                        ->label('Tasa COP → Bs')
                        ->numeric()
                        ->disabled(fn (callable $get) => $get('cop_locked'))
                        ->dehydrated(true),

                    Forms\Components\Hidden::make('usd_locked')->default(false),
                    Forms\Components\Hidden::make('cop_locked')->default(false),
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $usdRate = $data['usd_rate'];
                    $copRate = $data['cop_rate'];

                    if (!$month || !$usdRate || !$copRate) {
                        throw new \Exception('Debe completar todos los campos antes de continuar.');
                    }

                    $sectorId = Auth::user()->sector_id;

                    // ✅ Registrar tasa oficial del mes para deducción
                    ExchangeRate::firstOrCreate(
                        ['month' => $month, 'currency' => 'USD', 'operation' => 'D', 'sector_id' => $sectorId],
                        ['rate_to_bs' => $usdRate]
                    );

                    ExchangeRate::firstOrCreate(
                        ['month' => $month, 'currency' => 'COP', 'operation' => 'D', 'sector_id' => $sectorId],
                        ['rate_to_bs' => $copRate]
                    );

                    // ✅ Redireccionar al PDF
                    return redirect()->route('sector.report.pdf', [
                        'sector' => $sectorId,
                        'month' => $month,
                        'usd_rate' => $usdRate,
                        'cop_rate' => $copRate,
                    ]);
                }),


                
            
                

            Action::make('generar_pdf_distrital')
                ->label('PDF Deducción Distrital')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->visible(fn () => Auth::user()?->hasRole('Supervisor Distrital'))
                ->form([
                    Forms\Components\Select::make('month')
                        ->label('Seleccione el Mes')
                        ->options(
                            \App\Models\TreasuryAllocation::select('month')
                                ->distinct()
                                ->orderByDesc('month')
                                ->pluck('month')
                                ->filter(fn ($mes) => \Carbon\Carbon::parse($mes . '-01')->year === now()->year)
                                ->mapWithKeys(fn ($mes) => [
                                    $mes => \Carbon\Carbon::parse($mes . '-01')->translatedFormat('F/Y'),
                                ])
                                ->toArray()
                        )
                        ->required()
                        ->searchable()
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $districtId = Auth::user()?->district_id;

                    // Buscar todos los sectores del distrito
                    $sectores = \App\Models\Sector::where('district_id', $districtId)->pluck('id');

                    if ($sectores->isEmpty()) {
                        Notification::make()
                            ->title('Sin sectores')
                            ->body('El distrito no tiene sectores registrados.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Buscar cualquier sector del distrito que tenga tasas válidas
                    $sectorConTasas = $sectores->first(function ($sectorId) use ($month) {
                        $usdExists = \App\Models\ExchangeRate::where('month', $month)
                            ->where('currency', 'USD')
                            ->where('operation', 'D')
                            ->where('sector_id', $sectorId)
                            ->exists();
                        
                        $copExists = \App\Models\ExchangeRate::where('month', $month)
                            ->where('currency', 'COP')
                            ->where('operation', 'D')
                            ->where('sector_id', $sectorId)
                            ->exists();
                        
                        return $usdExists && $copExists;
                    });

                    if (!$sectorConTasas) {
                        Notification::make()
                            ->title('Sin tasas registradas')
                            ->body('Ningún sector del distrito tiene tasas registradas para este mes.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Obtener las tasas del sector que sí las tiene
                    $usdRate = \App\Models\ExchangeRate::where('month', $month)
                        ->where('currency', 'USD')
                        ->where('operation', 'D')
                        ->where('sector_id', $sectorConTasas)
                        ->value('rate_to_bs');

                    $copRate = \App\Models\ExchangeRate::where('month', $month)
                        ->where('currency', 'COP')
                        ->where('operation', 'D')
                        ->where('sector_id', $sectorConTasas)
                        ->value('rate_to_bs');

                    // ✅ Generar PDF
                    return redirect()->route('district.report.pdf', [
                        'district' => $districtId,
                        'month' => $month,
                        'usd_rate' => $usdRate,
                        'cop_rate' => $copRate,
                    ]);
                }),
                
            Action::make('generar_pdf_regional')
                ->label('PDF Deducción Regional')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->visible(fn () => Auth::user()?->hasRole('Tesorero Regional'))
                ->form([
                    Forms\Components\Select::make('month')
                        ->label('Seleccione el Mes')
                        ->options(
                            TreasuryAllocation::select('month')
                                ->distinct()
                                ->orderByDesc('month')
                                ->pluck('month')
                                ->filter(fn ($mes) => \Carbon\Carbon::parse($mes . '-01')->year === now()->year) // ✅ Corregido Carbon
                                ->mapWithKeys(fn ($mes) => [
                                    $mes => \Carbon\Carbon::parse($mes . '-01')->translatedFormat('F/Y'), // ✅ Corregido Carbon
                                ])
                                ->toArray()
                        )
                        ->required()
                        ->searchable()
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $regionId = Auth::user()?->region_id;

                    // 🔎 Buscar todos los distritos de la región
                    $distritos = \App\Models\District::where('region_id', $regionId)->pluck('id');

                    if ($distritos->isEmpty()) {
                        Notification::make()
                            ->title('Sin distritos')
                            ->body('La región no tiene distritos registrados.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // 🔎 Buscar todos los sectores de esos distritos
                    $sectores = \App\Models\Sector::whereIn('district_id', $distritos)->pluck('id');

                    if ($sectores->isEmpty()) {
                        Notification::make()
                            ->title('Sin sectores')
                            ->body('Los distritos de la región no tienen sectores registrados.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // ✅ Buscar cualquier sector de la región que tenga tasas válidas en ExchangeRate
                    $sectorConTasas = $sectores->first(function ($sectorId) use ($month) {
                        $usdExists = \App\Models\ExchangeRate::where('month', $month)
                            ->where('currency', 'USD')
                            ->where('operation', 'D')
                            ->where('sector_id', $sectorId)
                            ->exists();
                        
                        $copExists = \App\Models\ExchangeRate::where('month', $month)
                            ->where('currency', 'COP')
                            ->where('operation', 'D')
                            ->where('sector_id', $sectorId)
                            ->exists();
                        
                        return $usdExists && $copExists;
                    });

                    if (!$sectorConTasas) {
                        Notification::make()
                            ->title('Sin tasas registradas')
                            ->body('Ningún sector de la región tiene tasas registradas para este mes.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Obtener las tasas del sector que sí las tiene
                    $usdRate = \App\Models\ExchangeRate::where('month', $month)
                        ->where('currency', 'USD')
                        ->where('operation', 'D')
                        ->where('sector_id', $sectorConTasas)
                        ->value('rate_to_bs');

                    $copRate = \App\Models\ExchangeRate::where('month', $month)
                        ->where('currency', 'COP')
                        ->where('operation', 'D')
                        ->where('sector_id', $sectorConTasas)
                        ->value('rate_to_bs');

                    // ✅ Redireccionar al PDF
                    return redirect()->route('region.report.pdf', [
                        'region' => $regionId,
                        'month' => $month,
                        'usd_rate' => $usdRate,
                        'cop_rate' => $copRate,
                    ]);
                }),
                
            Action::make('generar_pdf_nacional')
                ->label('PDF Deducción Nacional')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->visible(fn () => Auth::user()?->hasRole('Tesorero Nacional'))
                ->form([
                    Forms\Components\Select::make('month')
                        ->label('Seleccione el Mes')
                        ->options(
                            \App\Models\TreasuryAllocation::select('month')
                                ->distinct()
                                ->orderByDesc('month')
                                ->pluck('month')
                                ->filter(fn ($mes) => \Carbon\Carbon::parse($mes . '-01')->year === now()->year)
                                ->mapWithKeys(fn ($mes) => [
                                    $mes => \Carbon\Carbon::parse($mes . '-01')->translatedFormat('F/Y'),
                                ])
                                ->toArray()
                        )
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // 🔍 PRIMERO: Buscar si ya existe tasa nacional (N)
                            $tasaNacional = \App\Models\ExchangeRate::where('month', $state)
                                ->where('currency', 'USD')
                                ->where('operation', 'N')
                                ->whereNotNull('sector_id')
                                ->value('rate_to_bs');

                            if ($tasaNacional) {
                                // Si ya existe tasa nacional, usarla y bloquear edición
                                $set('usd_rate', $tasaNacional);
                                $set('usd_locked', true);
                            } else {
                                // 🔍 SEGUNDO: Si no existe tasa nacional, buscar en tasas sectoriales (D)
                                $tasaSectorial = \App\Models\ExchangeRate::where('month', $state)
                                    ->where('currency', 'USD')
                                    ->where('operation', 'D') // ✅ Se alimenta de las tasas sectoriales
                                    ->whereNotNull('sector_id')
                                    ->value('rate_to_bs');

                                if ($tasaSectorial) {
                                    // Usar la tasa sectorial como referencia, pero permitir editarla
                                    $set('usd_rate', $tasaSectorial);
                                    $set('usd_locked', false); // Permite editarla para establecer la tasa nacional
                                } else {
                                    $set('usd_rate', null);
                                    $set('usd_locked', false);
                                }
                            }
                        }),

                    Forms\Components\TextInput::make('usd_rate')
                        ->label('Tasa USD → Bs')
                        ->numeric()
                        ->required()
                        ->disabled(fn (callable $get) => $get('usd_locked'))
                        ->dehydrated(true),

                    Forms\Components\Hidden::make('usd_locked')->default(false),
                ])
                ->action(function (array $data) {
                    $month = $data['month'];
                    $usdRate = $data['usd_rate'];

                    if (!$month || !$usdRate) {
                        throw new \Exception('Debe completar todos los campos antes de continuar.');
                    }

                    // ✅ Buscar cualquier sector para asociar la tasa nacional
                    $primerSector = \App\Models\Sector::first();

                    if (!$primerSector) {
                        throw new \Exception('No se encontró ningún sector en el sistema.');
                    }

                    $sectorId = $primerSector->id;

                    // ✅ Registrar/actualizar tasa nacional (operation = 'N')
                    \App\Models\ExchangeRate::firstOrCreate(
                        ['month' => $month, 'currency' => 'USD', 'operation' => 'N', 'sector_id' => $sectorId],
                        ['rate_to_bs' => $usdRate]
                    );

                    return redirect()->route('national.report.pdf', [
                        'month' => $month,
                        'usd_rate' => $usdRate,
                    ]);
                }),


            Action::make('export_top_200_churches')
                ->label('200 Iglesias con mayor...')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->visible(fn () => Auth::user()?->hasRole('Tesorero Nacional'))
                ->form([
                    Forms\Components\Select::make('categoria')
                        ->label('Categoría de Ofrenda')
                        ->native(false)
                        ->options([
                            'diezmos' => 'Diezmos',
                            'el_poder_del_uno' => 'El Poder del Uno',
                            'sede_nacional' => 'Sede Nacional',
                            'unica_sectorial' => 'Única Sectorial',
                        ])
                        ->required(),

                    Forms\Components\ToggleButtons::make('formato')
                        ->label('Formato de Exportación')
                        ->options([
                            'pdf' => 'PDF',
                            'xlsx' => 'Excel',
                        ])
                        ->default('pdf')
                        ->colors([
                            'pdf' => 'danger',
                            'xlsx' => 'success',
                        ])
                        ->required()
                        ->inline(),

                    Forms\Components\Select::make('periodo')
                        ->label('Rango de Tiempo')
                        ->native(false)
                        ->options([
                            'mes' => 'Mes',
                            'trimestre' => 'Último Trimestre',
                            'semestre' => 'Último Semestre',
                            'anual' => 'Último Año',
                        ])
                        ->default('mes')
                        ->required()
                        ->reactive(), // 🔁 Habilita cambios dinámicos

                    Forms\Components\TextInput::make('referencia')
                        ->label('Referencia del Periodo')
                        ->placeholder('Ej: 2025-05')
                        ->required()
                        ->helperText(fn (callable $get) => match ($get('periodo')) {
                            'mes' => 'Formato: YYYY-MM (Ej: 2025-05)',
                            'trimestre' => 'Trimestre de referencia (Ej: 2025-T1)',
                            'semestre' => 'Semestre de referencia (Ej: 2025-S1)',
                            'anual' => 'Año completo (Ej: 2025)',
                            default => 'Ej: 2025-05',
                        }),

                ])
                ->action(function (array $data) {
                    $servicio = new TopChurchesOfferingsReportService();
                    $datos = $servicio->obtenerTopIglesias(
                        $data['periodo'],
                        $data['categoria'],
                        $data['referencia']
                    );

                    if ($datos->isEmpty()) {
                        Notification::make()
                            ->title('Sin resultados')
                            ->body('No hay registros para el período seleccionado.')
                ->warning()
                ->send();

            return;
        }

        // ✅ Si hay datos, redirigir a la descarga
        $queryParams = http_build_query([
            'categoria'  => $data['categoria'],
            'formato'    => $data['formato'],
            'periodo'    => $data['periodo'],
            'referencia' => $data['referencia'],
        ]);

        return redirect()->to("/reportes/top-churches/export?$queryParams");
    }),


            
        ];
    }
}