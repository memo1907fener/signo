<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Passwort vergessen')]
class ForgotPassword extends Component
{
    public string $email = '';
    public bool $sent = false;

    public function send(): void
    {
        $this->validate(['email' => 'required|email']);
        $key = 'forgot:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', __('Zu viele Anfragen. Bitte später erneut versuchen.'));
            return;
        }
        RateLimiter::hit($key, 3600);
        Password::sendResetLink(['email' => strtolower(trim($this->email))]);
        // Immer dieselbe Antwort – verrät nicht, ob ein Konto existiert
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
