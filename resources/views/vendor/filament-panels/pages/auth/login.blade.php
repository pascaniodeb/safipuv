<x-filament-panels::page.simple>
    <x-slot name="heading">
        {{ __('Iniciar Sesión') }}
    </x-slot>

    <form wire:submit.prevent="authenticate" class="space-y-6">
        <!-- Campo de ID Personal -->
        <div>
            <label class="block mb-2 text-md font-medium text-gray-900 dark:text-white">Cédula</label>
            <input
                type="text"
                wire:model.defer="username"
                class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                placeholder="Ingrese su cédula"
                required
                autofocus
            />
            @error('username') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Campo de Contraseña -->
        <div>
            <label class="block mb-2 text-md font-medium text-gray-900 dark:text-white">Contraseña</label>
            <input
                type="password"
                wire:model.defer="password"
                class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                placeholder="********"
                required
            />
            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Botón de Iniciar Sesión -->
        <div class="mt-8 flex justify-end">
            <button
                type="submit"
                class="inline-flex justify-center w-full py-2 px-4 border border-transparent shadow-sm text-md font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-primary-600 dark:hover:bg-primary-700"
            >
                Iniciar Sesión
            </button>
        </div>
    </form>
</x-filament-panels::page.simple>
