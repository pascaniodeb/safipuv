<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPastorRecord
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Verifica si el usuario es un pastor y no tiene un registro asociado
        if ($user->hasRole('Pastor') && !\App\Models\Pastor::where('user_id', $user->id)->exists()) {
            session()->flash('notification', [
                'status' => 'error',
                'message' => 'No tienes un registro asociado como pastor. Contacta al administrador.',
            ]);

            return redirect('/admin'); // Ajusta según la URL del dashboard
        }

        return $next($request);
    }
}