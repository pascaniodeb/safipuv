<x-filament::page>
    <div class="space-y-4">
        <h2 class="text-xl font-bold">Sincronización de Pastores y Usuarios</h2>

        <p class="text-sm text-gray-600">
            Esta acción sincronizará todos los registros de <strong>Pastores</strong> con la tabla de <strong>Usuarios</strong>. Se crearán nuevos registros si no existen, y se actualizarán los existentes.
        </p>

        {{-- Botón para ejecutar la sincronización --}}
        <x-filament::button wire:click="sync">
            Ejecutar Sincronización
        </x-filament::button>

        {{-- Tabla de registros sincronizados --}}
        <div class="mt-6">
            <h3 class="text-md font-semibold mb-2">Historial de Sincronizaciones Recientes</h3>

            <table class="w-full text-xs border border-gray-300">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-2 border">#</th>
                        <th class="p-2 border">Cédula</th>
                        <th class="p-2 border">Nombre</th>
                        <th class="p-2 border">Acción</th>
                        <th class="p-2 border">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $i => $log)
                        <tr class="border-t">
                            <td class="p-2 border">{{ $i + 1 }}</td>
                            <td class="p-2 border">{{ $log->username }}</td>
                            <td class="p-2 border">{{ $log->full_name }}</td>
                            <td class="p-2 border">
                                <span class="inline-block px-2 py-1 rounded text-white text-xs {{ $log->action === 'creado' ? 'bg-green-600' : 'bg-yellow-500' }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="p-2 border">{{ $log->synced_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach

                    @if ($logs->isEmpty())
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500">
                                No hay registros de sincronización recientes.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>


