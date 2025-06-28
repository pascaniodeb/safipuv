<?php

namespace App\Providers;

use App\Models\Pastor;
use App\Models\Message;
use App\Observers\MessageObserver;
use App\Observers\PastorObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observador para el modelo Pastor
        // Esto permite que cada vez que se cree, actualice o elimine un pastor, se ejecute el observador
        // y se realicen las acciones correspondientes en el modelo User.
        // Esto es útil para mantener la sincronización entre los modelos Pastor y User.
        // Puedes usar el método observe para registrar el observador
        // y asociarlo con el modelo Pastor.
    
        Pastor::observe(PastorObserver::class);
        Message::observe(\App\Observers\MessageObserver::class);

        // DEBUG: Temporal para verificar registro
        \Illuminate\Support\Facades\Log::info('MessageObserver registrado en AppServiceProvider');
    }
}