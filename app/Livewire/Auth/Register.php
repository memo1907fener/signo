<?php

namespace App\Livewire\Auth;

use App\Models\Organization;
use App\Models\User;
use App\Support\Disposable;
use App\Support\Starter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Kostenlos testen')]
class Register extends Component
{
    public string $industry = '';
    public string $company = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $terms = false;
    public string $website = '';   // Honigtopf – Menschen sehen das Feld nicht
    public int $startedAt = 0;

    public function mount(): void
    {
        $this->startedAt = time();
        $i = request('branche');
        if ($i && array_key_exists($i, config('signo.industries'))) $this->industry = $i;
    }

    public function register()
    {
        $ip = request()->ip();
        $key = 'register:' . $ip;

        // Bots: Honigtopf ausgefüllt oder Formular in unter 3 Sekunden abgeschickt
        if ($this->website !== '' || (time() - $this->startedAt) < 3) {
            $this->addError('email', 'Die Registrierung konnte nicht abgeschlossen werden. Bitte versuchen Sie es erneut.');
            return;
        }
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('email', 'Zu viele Registrierungen von dieser Verbindung. Bitte später erneut versuchen.');
            return;
        }

        $this->email = strtolower(trim($this->email));
        $this->validate([
            'industry' => 'required|in:' . implode(',', array_keys(config('signo.industries'))),
            'company' => 'required|string|max:120',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|max:200',
            'terms' => 'accepted',
        ], [
            'industry.required' => 'Bitte wählen Sie Ihre Branche.',
            'company.required' => 'Bitte geben Sie den Namen Ihres Betriebs an.',
            'name.required' => 'Bitte geben Sie Ihren Namen an.',
            'email.unique' => 'Mit dieser E-Mail-Adresse gibt es bereits ein Konto.',
            'password.min' => 'Das Passwort braucht mindestens 8 Zeichen.',
            'terms.accepted' => 'Bitte stimmen Sie den AGB und der Datenschutzerklärung zu.',
        ]);

        if (Disposable::is($this->email)) {
            $this->addError('email', 'Bitte verwenden Sie eine dauerhafte E-Mail-Adresse.');
            return;
        }

        RateLimiter::hit($key, 3600);

        $user = DB::transaction(function () use ($ip) {
            $org = Organization::create([
                'name' => $this->company,
                'industry' => $this->industry,
                'trial_ends_at' => now()->addDays(config('signo.trial_days')),
                'signup_ip' => $ip,
            ]);
            Starter::create($org);

            return User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'organization_id' => $org->id,
                'is_admin' => in_array($this->email, array_map('strtolower', config('signo.admin_emails')), true),
            ]);
        });

        Auth::login($user, true);
        session()->regenerate();

        return $this->redirectRoute('pages.index', navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
