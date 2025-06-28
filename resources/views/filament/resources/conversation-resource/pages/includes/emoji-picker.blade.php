<div class="relative" x-data="{ open: false }">
    <button type="button" 
            @click="open = !open"
            @click.away="open = false"
            class="flex-shrink-0 p-1.5 text-base hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
        😊
    </button>

    <!-- Picker de emojis estilo fila horizontal -->
    <div x-show="open" 
         x-transition
         class="absolute bottom-full mb-1 left-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-2 shadow-lg z-50"
         style="min-width: 260px; max-width: 100%; white-space: nowrap;">
         
        <div class="flex flex-wrap gap-1 max-w-full overflow-x-auto">
            @foreach(['👍', '❤️', '😂', '😍', '🤔', '😭', '😴', '🙏', '👏', '🎉', '🔥', '💯', '😊', '🚀', '⚡', '✨'] as $emoji)
                <button type="button" 
                        wire:click="addEmoji('{{ $emoji }}')"
                        @click="open = false"
                        class="p-1 text-xl hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors">
                    {{ $emoji }}
                </button>
            @endforeach
        </div>
    </div>
</div>
