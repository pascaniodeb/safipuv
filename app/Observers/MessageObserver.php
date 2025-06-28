<?php

namespace App\Observers;

use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MessageObserver
{
    public function created(Message $message): void
    {
        Log::info('MessageObserver: Nuevo mensaje creado ID: ' . $message->id);
        
        // Verificar que la conversación existe
        if (!$message->conversation) {
            Log::error('MessageObserver: Conversación no encontrada');
            return;
        }
        
        // Obtener todos los participantes excepto el remitente
        $participants = $message->conversation->participants()
            ->where('users.id', '!=', $message->sender_id)
            ->get();

        Log::info('MessageObserver: Participantes encontrados: ' . $participants->count());

        foreach ($participants as $participant) {
            Log::info('MessageObserver: Enviando notificación a: ' . $participant->name . ' (ID: ' . $participant->id . ')');
            
            try {
                // MÉTODO 1: Crear notificación directamente en la base de datos
                $participant->notifications()->create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'App\\Notifications\\NewMessageNotification',
                    'data' => [
                        'title' => '💬 Nuevo mensaje',
                        'body' => "**{$message->sender->name}** te ha enviado un mensaje en: {$message->conversation->subject}",
                        'message_id' => $message->id,
                        'conversation_id' => $message->conversation_id,
                        'sender_name' => $message->sender->name,
                        'conversation_subject' => $message->conversation->subject,
                        'url' => route('filament.admin.resources.conversations.view', $message->conversation_id),
                        'icon' => 'heroicon-o-chat-bubble-left-right',
                        'color' => 'success'
                    ],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::info('MessageObserver: Notificación guardada en BD para: ' . $participant->name);
                
                // MÉTODO 2: También mostrar notificación toast si el usuario está online
                if ($participant->id === auth()->id()) {
                    \Filament\Notifications\Notification::make()
                        ->title('💬 Nuevo mensaje')
                        ->body("**{$message->sender->name}** te ha enviado un mensaje")
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->duration(5000)
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('Ver conversación')
                                ->button()
                                ->url(route('filament.admin.resources.conversations.view', $message->conversation_id))
                                ->close(),
                        ])
                        ->send();
                }
                
            } catch (\Exception $e) {
                Log::error('MessageObserver: Error enviando notificación: ' . $e->getMessage());
                Log::error('MessageObserver: Stack trace: ' . $e->getTraceAsString());
            }
        }
        
        Log::info('MessageObserver: Proceso completado');
    }
}