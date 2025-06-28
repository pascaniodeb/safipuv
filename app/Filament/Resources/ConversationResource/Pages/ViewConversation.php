<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use App\Models\Message;
use App\Models\MessageAttachment;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Livewire\WithFileUploads;
use Filament\Notifications\Notification;

class ViewConversation extends ViewRecord
{
    use WithFileUploads;

    protected static string $resource = ConversationResource::class;
    protected static string $view = 'filament.resources.conversation-resource.pages.view-conversation';
    
    public $messageContent = '';
    public $attachments = [];
    public $isTyping = false;
    
    public function getTitle(): string
    {
        return $this->record->subject;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('markAsRead')
                ->label('Marcar como leído')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->action(function () {
                    $this->record->messages()
                        ->where('sender_id', '!=', auth()->id())
                        ->whereNull('read_at')
                        ->update(['read_at' => now()]);
                    
                    Notification::make()
                        ->title('Mensajes marcados como leídos')
                        ->success()
                        ->send();
                }),
            
            Actions\Action::make('refreshMessages')
                ->label('Actualizar')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('refreshMessages');
                }),
        ];
    }

    public function sendMessage()
    {
        $this->validate([
            'messageContent' => 'required_without:attachments|string|max:2000',
            'attachments.*' => 'file|max:10240', // 10MB por archivo
        ]);

        try {
            // Verificar permisos
            if (!auth()->user()->can('view', $this->record)) {
                throw new \Exception('No tienes permisos para enviar mensajes en esta conversación');
            }

            // Crear el mensaje
            $message = Message::create([
                'conversation_id' => $this->record->id,
                'sender_id' => auth()->id(),
                'content' => $this->messageContent,
                'type' => 'text',
            ]);

            // Procesar archivos adjuntos
            if (!empty($this->attachments)) {
                foreach ($this->attachments as $file) {
                    // Generar nombre único para evitar conflictos
                    $filename = uniqid() . '_' . time() . '_' . $file->getClientOriginalName();
                    
                    // Guardar archivo
                    $file->storeAs('message-attachments', $filename, 'message_attachments');
                    
                    // Crear registro en base de datos
                    MessageAttachment::create([
                        'message_id' => $message->id,
                        'filename' => $filename,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'path' => $filename,
                    ]);
                }
            }

            // Actualizar timestamp de la conversación
            $this->record->touch();

            // Limpiar formulario
            $this->messageContent = '';
            $this->attachments = [];

            // Notificación de éxito
            Notification::make()
                ->title('Mensaje enviado')
                ->success()
                ->send();

            // Refrescar vista
            $this->dispatch('messagesSent');

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al enviar mensaje')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function addEmoji($emoji)
    {
        $this->messageContent .= $emoji;
    }

    public function setTyping($typing)
    {
        $this->isTyping = $typing;
    }

    protected function getViewData(): array
    {
        return [
            'messages' => $this->record->messages()
                ->with(['sender', 'attachments'])
                ->orderBy('created_at', 'asc')
                ->get(),
            'participants' => $this->record->participants,
        ];
    }

    // Método para auto-refresh sin interrumpir la escritura
    public function refreshMessages()
    {
        if (!$this->isTyping) {
            $this->dispatch('$refresh');
        }
    }

    // Polling solo cuando no está escribiendo
    public function getListeners()
    {
        return [
            'refreshMessages' => 'refreshMessages',
            'setTyping' => 'setTyping',
        ];
    }
}