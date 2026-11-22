<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Neues Passwort')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request('email', '');
    }

    public function save()
    {
        $this->validate(['email' => 'required|email', 'password' => 'required|min:8'], ['password.min' => 'Mindestens 8 Zeichen.']);
        $status = Password::reset(
            ['email' => strtolower(trim($this->email)), 'password' => $this->password, 'token' => $this->token],
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
                Auth::login($user, true);
            }
        );
        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', 'Der Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.');
            return;
        }
        session()->flash('ok', 'Ihr Passwort wurde geändert.');
        return $this->redirectRoute('pages.index', navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
