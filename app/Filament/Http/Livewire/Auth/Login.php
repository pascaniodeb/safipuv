<?php

namespace App\Filament\Http\Livewire\Auth;

use App\Filament\Http\Livewire\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    protected function attemptLogin(): bool
    {
        return Auth::attempt([
            'username' => $this->data['email'],
            'password' => $this->data['password'],
            'remember' => $this->remember, // Añade esta línea
        ]);
    }
}
