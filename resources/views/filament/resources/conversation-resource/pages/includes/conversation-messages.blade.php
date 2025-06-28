<!-- Chat con Input Integrado - SIN SCROLL EXTERNO -->
<div class="flex-1 min-h-0 overflow-hidden">
    <x-filament::section>
        <x-slot name="heading">
            💬 Conversación
        </x-slot>
        
        <!-- Contenedor principal del chat - ALTURA FIJA -->
        <div class="flex flex-col" style="height: calc(50vh - 50px); overflow-y: auto;">
            <!-- Área de mensajes (scrolleable SOLO INTERNA) -->
            <div class="flex-1 space-y-3 overflow-y-auto p-3 bg-gray-50 dark:bg-gray-800 rounded-t-lg" 
                 id="messages-container"
                 wire:poll.10s="refreshMessages">
                @forelse($messages as $message)
                    <div class="message-item {{ $message->sender_id === auth()->id() ? 'message-own' : 'message-other' }}">
                        
                        @if($message->sender_id === auth()->id())
                            <!-- Mis mensajes (derecha) -->
                            <div style="display: flex; justify-content: flex-end; margin-bottom: 0.8rem;">
                                <div style="max-width: 70%; background: #3b82f6; color: white; padding: 10px 14px; border-radius: 16px 16px 4px 16px; word-wrap: break-word; user-select: text;">
                                    <div style="font-size: 14px; line-height: 1.4; white-space: pre-wrap;">{{ $message->content }}</div>
                                    
                                    @include('filament.resources.conversation-resource.pages.includes.message-attachments', ['message' => $message, 'isOwn' => true])
                                    
                                    <div style="font-size: 10px; margin-top: 6px; opacity: 0.8; text-align: right;">
                                        {{ $message->created_at->format('H:i') }}
                                        @if($message->read_at) ✓✓ @else ✓ @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mi avatar PEQUEÑO -->
                            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 6px; margin-bottom: 6px;">
                                <span style="font-size: 11px; color: #6b7280;">Tú</span>
                                <div style="width: 24px; height: 24px; background: linear-gradient(45deg, #3b82f6, #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            </div>
                        @else
                            <!-- Mensajes de otros (izquierda) -->
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                <div style="width: 24px; height: 24px; background: linear-gradient(45deg, #8b5cf6, #7c3aed); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                                    {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                                </div>
                                <span style="font-size: 11px; font-weight: 600; color: #374151;">{{ $message->sender->name }}</span>
                            </div>
                            
                            <div style="display: flex; justify-content: flex-start; margin-bottom: 0.8rem;">
                                <div style="max-width: 70%; background: white; border: 1px solid #e5e7eb; color: #1f2937; padding: 10px 14px; border-radius: 16px 16px 16px 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); word-wrap: break-word; user-select: text;">
                                    <div style="font-size: 14px; line-height: 1.4; white-space: pre-wrap;">{{ $message->content }}</div>
                                    
                                    @include('filament.resources.conversation-resource.pages.includes.message-attachments', ['message' => $message, 'isOwn' => false])
                                    
                                    <div style="font-size: 10px; margin-top: 6px; color: #6b7280;">
                                        {{ $message->created_at->format('H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="text-align: center; padding: 1.5rem 0;">
                        <div style="font-size: 2rem; margin-bottom: 0.8rem;">💬</div>
                        <h3 style="font-size: 0.9rem; font-weight: 600; color: #374151; margin-bottom: 0.3rem;">No hay mensajes aún</h3>
                        <p style="color: #6b7280; font-size: 0.8rem;">¡Escribe el primer mensaje!</p>
                    </div>
                @endforelse
            </div>

            <!-- Input fijo en la parte inferior - MÁS COMPACTO -->
            <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-900 rounded-b-lg">
                <form wire:submit="sendMessage" class="space-y-2">
                    <div class="flex items-end gap-2">
                        <!-- Botón de emojis -->
                        @include('filament.resources.conversation-resource.pages.includes.emoji-picker')
                        
                        <!-- Área de texto MÁS PEQUEÑA -->
                        <div class="flex-1">
                            <textarea wire:model.defer="messageContent"
                                      wire:keydown.enter.prevent="sendMessage"
                                      wire:input="setTyping(true)"
                                      wire:blur="setTyping(false)"
                                      placeholder="Escribe aquí..."
                                      class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white text-sm transition-all"
                                      rows="2"
                                      style="max-height: 60px; line-height: 1.3;"
                                      x-data
                                      x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 60) + 'px'"></textarea>
                        </div>
                        
                        <!-- Botones de acciones MÁS PEQUEÑOS -->
                        <div class="flex gap-1">
                            <!-- Botón de archivo -->
                            <label class="flex-shrink-0 p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full cursor-pointer transition">
                                <x-heroicon-o-paper-clip class="w-5 h-5" />
                                <input type="file" 
                                    wire:model="attachments" 
                                    multiple 
                                    accept="image/*,audio/*,.pdf,.doc,.docx,.txt"
                                    class="hidden">
                            </label>

                            
                            <!-- Botón de enviar -->
                            <button type="submit" 
                                    class="flex-shrink-0 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm font-medium text-sm"
                                    wire:loading.attr="disabled"
                                    wire:target="sendMessage">
                                <span wire:loading.remove wire:target="sendMessage" class="text-white">Enviar</span>
                                <span wire:loading wire:target="sendMessage" class="text-white">Enviando...</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Archivos seleccionados -->
                    @include('filament.resources.conversation-resource.pages.includes.selected-files')
                </form>
            </div>
        </div>
    </x-filament::section>
</div>