<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    public $username;
    public $password;
    public $remember = false; // Para el checkbox "Recuérdame"

    public function authenticate(): ?LoginResponse
    {
        $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt(['username' => $this->username, 'password' => $this->password], $this->remember)) {
            $this->addError('username', __('auth.failed'));
            return null;
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
