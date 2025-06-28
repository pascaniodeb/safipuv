{{-- resources/views/filament/widgets/exchange-rate-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="p-6">
            {{-- Header del widget --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-building-office-2 class="w-6 h-6 text-blue-600" />
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                        Tasa de Cambio Oficial
                    </h3>
                </div>
                <div class="flex items-center space-x-2">
                    {{-- Indicador de estado --}}
                    @if($isToday)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full dark:bg-green-900/20 dark:text-green-400">
                            <x-heroicon-m-check-circle class="w-3 h-3 mr-1" />
                            Actualizada hoy
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full dark:bg-yellow-900/20 dark:text-yellow-400">
                            <x-heroicon-m-clock class="w-3 h-3 mr-1" />
                            Revisar tasas
                        </span>
                    @endif
                    
                    {{-- Botón de refresh --}}
                    <button 
                        wire:click="refreshRates"
                        class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full transition-colors"
                        title="Actualizar tasas"
                        wire:loading.attr="disabled"
                    >
                        <x-heroicon-o-arrow-path class="w-5 h-5" wire:loading.class="animate-spin" />
                    </button>
                </div>
            </div>

            @if($hasData)
                {{-- Información del Banco Central --}}
                <div class="text-center mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                        IGLESIA PENTECOSTAL UNIDA DE VENEZUELA
                    </p>
                    <div class="flex items-center justify-center space-x-1 mt-1">
                        <x-heroicon-o-calendar class="w-4 h-4 text-green-600" />
                        <span class="text-sm text-green-600 font-semibold">
                            FECHA: {{ $lastUpdated }}
                        </span>
                    </div>
                </div>

                {{-- Tasas de cambio --}}
                <div class="space-y-4">
                    {{-- USD Rate --}}
                    @if($exchangeData['usd_rate'] > 0)
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-l-4 border-blue-500">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-currency-dollar class="w-5 h-5 text-blue-600" />
                                    <div>
                                        <span class="font-semibold text-blue-800 dark:text-blue-300">
                                            Bs/DÓLARES:
                                        </span>
                                        <div class="text-xs text-blue-600 dark:text-blue-400">
                                            @if($exchangeData['usd_operation'] === '/')
                                                División (÷)
                                            @elseif($exchangeData['usd_operation'] === '*')
                                                Multiplicación (×)
                                            @else
                                                {{ $exchangeData['usd_operation'] }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-blue-800 dark:text-blue-300">
                                        {{ number_format($exchangeData['usd_rate'], 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- COP Rate --}}
                    @if($exchangeData['cop_rate'] > 0)
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border-l-4 border-green-500">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-o-currency-dollar class="w-5 h-5 text-green-600" />
                                    <div>
                                        <span class="font-semibold text-green-800 dark:text-green-300">
                                            Bs/PESOS:
                                        </span>
                                        <div class="text-xs text-green-600 dark:text-green-400">
                                            @if($exchangeData['cop_operation'] === '*')
                                                Multiplicación (×)
                                            @elseif($exchangeData['cop_operation'] === '/')
                                                División (÷)
                                            @else
                                                {{ $exchangeData['cop_operation'] }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-green-800 dark:text-green-300">
                                        {{ number_format($exchangeData['cop_rate'], 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer con información adicional --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                        <p>• Reportes sectoriales: del 1 al 8 de cada mes</p>
                        <p>• Reportes distritales: antes del 10 de cada mes</p>
                        @if($exchangeData['last_update'])
                            <p>• Última actualización: {{ $exchangeData['last_update']->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                    {{--class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                        Acceso autorizado para: 
                        <span class="font-semibold">{{ $userRole }}</span>
                    </div>--}}
                </div>
            @else
                {{-- Mensaje cuando no hay datos --}}
                <div class="text-center py-8">
                    <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-yellow-500 mx-auto mb-3" />
                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        No hay tasas disponibles
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        El Tesorero Nacional debe establecer las tasas del sistema.
                    </p>
                    @if(Auth::user()->role === 'Tesorero Nacional')
                        <a href="{{ route('filament.admin.resources.exchange-rates.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors">
                            <x-heroicon-m-pencil class="w-4 h-4 mr-2" />
                            Configurar Tasas
                        </a>
                    @endif
                </div>
            @endif

            {{-- Indicador de carga --}}
            <div wire:loading.flex class="absolute inset-0 bg-white/75 dark:bg-gray-900/75 items-center justify-center rounded-lg">
                <div class="flex items-center space-x-2 text-blue-600">
                    <div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-sm font-medium">Actualizando tasas...</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
