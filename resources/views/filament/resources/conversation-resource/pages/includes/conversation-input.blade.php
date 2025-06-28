<!-- Sección de envío de mensajes (fijo abajo) -->
<div class="flex-shrink-0">
    <x-filament::section>
        <x-slot name="heading">
            ✏️ Enviar Mensaje
        </x-slot>

        <form wire:submit="sendMessage" class="space-y-4">
            <div class="flex items-end gap-3">
                <!-- Botón de emojis -->
                @include('filament.resources.conversation-resource.pages.includes.emoji-picker')
                
                <!-- Área de texto -->
                <div class="flex-1">
                    <textarea wire:model.live.debounce.300ms="messageContent"
                              wire:keydown.enter.prevent="sendMessage"
                              wire:input="setTyping(true)"
                              wire:blur="setTyping(false)"
                              placeholder="Escribe tu mensaje aquí..."
                              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white text-sm transition-all"
                              rows="2"
                              style="max-height: 100px; line-height: 1.4;"
                              x-data
                              x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 100) + 'px'"></textarea>
                </div>
                
                <!-- Botones de acciones -->
                <div class="flex gap-2">
                    <!-- Botón de archivo -->
                    <label class="flex-shrink-0 p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full cursor-pointer transition-colors">
                        <span class="text-lg">📎</span>
                        <input type="file" 
                               wire:model="attachments" 
                               multiple 
                               accept="image/*,audio/*,.pdf,.doc,.docx,.txt"
                               class="hidden">
                    </label>
                    
                    <!-- Botón de enviar -->
                    <button type="submit" 
                            class="flex-shrink-0 px-4 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-md font-medium text-sm"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage">
                        <span wire:loading.remove wire:target="sendMessage">Enviar 📤</span>
                        <span wire:loading wire:target="sendMessage">...</span>
                    </button>
                </div>
            </div>
            
            <!-- Archivos seleccionados -->
            @include('filament.resources.conversation-resource.pages.includes.selected-files')
        </form>
    </x-filament::section>
</div>