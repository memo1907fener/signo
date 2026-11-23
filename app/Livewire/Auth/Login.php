<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Anmelden')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = true;

    public function login()
    {
        $this->validate(['email' => 'required|email', 'password' => 'required']);
        $key = 'login:' . Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError('email', 'Zu viele Versuche. Bitte in ' . RateLimiter::availableIn($key) . ' Sekunden erneut versuchen.');
            return;
        }
        if (! Auth::attempt(['email' => Str::lower(trim($this->email)), 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 300);
            $this->addError('email', __('E-Mail-Adresse oder Passwort stimmen nicht.'));
            return;
        }
        RateLimiter::clear($key);
        session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();

        return $this->redirectIntended(route('pages.index'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
