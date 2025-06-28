<!-- Header de la conversación ULTRA COMPACTO -->
<div>
    <x-filament::section class="py-2">
        <x-slot name="heading">
            <div>
                <span class="truncate">{{ $record->subject }}</span>
                <div class="flex items-center gap-1">
                    @if($record->type === 'private')
                        <x-filament::badge color="success" size="sm">🔒</x-filament::badge>
                    @else
                        <x-filament::badge color="primary" size="sm">👥</x-filament::badge>
                    @endif
                    <x-filament::badge color="gray" size="sm">{{ $messages->count() }}</x-filament::badge>
                </div>
            </div>
        </x-slot>
        
        <!-- Info súper compacta -->
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span class="truncate">{{ $record->creator->name }} • {{ $record->created_at->format('d/m H:i') }}</span>
            @if($participants->count() > 2)
                <span>+{{ $participants->count() - 1 }} personas</span>
            @endif
        </div>
    </x-filament::section>
</div>