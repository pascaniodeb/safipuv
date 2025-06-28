<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use App\Models\SyncLog;
use Livewire\Attributes\On;
use Filament\Resources\Pages\Page as ResourcePage;

class SyncPastoresUsuarios extends ResourcePage
{
    protected static string $resource = \App\Filament\Resources\UserResource::class;
    protected static string $view = 'filament.resources.user-resource.pages.sync-pastores-usuarios';

    public array $logs = [];

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check() && auth()->user()->hasRole('Administrador');
    }

    public function mount()
    {
        $this->loadLogs();
    }

    public function sync()
    {
        Artisan::call('sync:pastores-a-usuarios');

        $this->loadLogs();

        Notification::make()
            ->title('Sincronización completada')
            ->success()
            ->body('La sincronización de pastores fue ejecutada correctamente.')
            ->send();
    }

    public function loadLogs()
    {
        $this->logs = SyncLog::latest('synced_at')->take(10)->get()->toArray();
    }
}