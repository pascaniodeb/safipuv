{{-- resources/views/filament/resources/accounting-transaction-resource/widgets/accounting-transaction-stats.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 w-full">
            {{-- INGRESOS --}}
            <div class="p-4 border rounded shadow bg-white">
                <div class="flex items-center space-x-2 text-green-600">
                    <x-heroicon-s-arrow-down-tray class="w-5 h-5" />
                    <h2 class="text-lg font-semibold uppercase">Ingresos</h2>
                </div>
                <div class="mt-2 text-sm text-gray-700 whitespace-pre-line">
                    <pre>{{ $this->ingStr }}</pre>
                </div>
            </div>

            {{-- EGRESOS --}}
            <div class="p-4 border rounded shadow bg-white">
                <div class="flex items-center space-x-2 text-red-600">
                    <x-heroicon-s-arrow-up-tray class="w-5 h-5" />
                    <h2 class="text-lg font-semibold uppercase">Egresos</h2>
                </div>
                <div class="mt-2 text-sm text-gray-700 whitespace-pre-line">
                    <pre>{{ $this->egrStr }}</pre>
                </div>
            </div>

            {{-- SALDO --}}
            <div class="p-4 border rounded shadow bg-white">
                <div class="flex items-center space-x-2 text-blue-600">
                    <x-heroicon-o-banknotes class="w-5 h-5" />
                    <h2 class="text-lg font-semibold uppercase">Saldo</h2>
                </div>
                <div class="mt-2 text-sm text-gray-700 whitespace-pre-line">
                    <pre>{{ $this->saldoStr }}</pre>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
@push('styles')
    <style>
        .filament-widgets-accounting-transaction-stats {
            @apply p-4 bg-white rounded-lg shadow;
        }
    </style>
@endpush




