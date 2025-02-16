<x-filament-widgets::widget>
    <x-filament::section>
        <div class="p-4">
            @php
                // Establece la zona horaria en UTC-4
                $currentTime = now()->setTimezone('America/Caracas'); // Usa el identificador de la región que está en UTC-4
                $hour = $currentTime->hour;
                $greeting = '';

                // Define el saludo basado en la hora
                if ($hour >= 0 && $hour < 12) {
                    $greeting = 'Buenos días';
                } elseif ($hour >= 12 && $hour < 19) {
                    $greeting = 'Buenas tardes';
                } else {
                    $greeting = 'Buenas noches';
                }

                // Obtén los datos del usuario autenticado
                $user = auth()->user();
            @endphp


            <h1 class="text-2xl font-bold mb-2">
                {{ $greeting }}, {{ $user->name }}
            </h1>
            <h1 class="text-xl  text-gray-400 font-semibold mt-2">
                {{ $user->getRoleNames()->join(', ') }}
            </h1>



            <p class="text-sm text-gray-600">
                Hoy es {{ $currentTime->translatedFormat('l, d \\d\\e F \\d\\e Y') }}.
            </p>

            <div class="mt-4">
                <p class="text-lg font-semibold text-primary-600">
                    {{ __('Versículo del día:') }}
                </p>
                <blockquote class="mt-2 text-gray-700 italic">
                    "{{ $verse }}"
                </blockquote>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

