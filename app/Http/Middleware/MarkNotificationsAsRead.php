<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MarkNotificationsAsRead
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Si estamos viendo una conversación, marcar notificaciones relacionadas como leídas
        if ($request->route('record') && $request->routeIs('filament.admin.resources.conversations.view')) {
            $conversationId = $request->route('record');
            
            auth()->user()
                ->unreadNotifications()
                ->where('type', 'filament.database')
                ->where('data->title', '💬 Nuevo mensaje')
                ->whereJsonContains('data->actions', [
                    'url' => route('filament.admin.resources.conversations.view', $conversationId)
                ])
                ->update(['read_at' => now()]);
        }

        return $response;
    }
}