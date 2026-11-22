<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithOrg;
use App\Services\Stripe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Konto')]
class Account extends Component
{
    use WithOrg;

    public string $company = '';
    public string $industry = '';
    public string $name = '';
    public string $email = '';
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $deletePassword = '';
    public ?string $saved = null;
    public ?string $billingError = null;

    public function mount(): void
    {
        $org = $this->org();
        $this->company = $org->name;
        $this->industry = $org->industry;
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
        if (request('bezahlt')) session()->flash('ok', 'Danke! Ihr Paket ist aktiv. Die Bestätigung kommt per E-Mail.');
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $this->email = strtolower(trim($this->email));
        $this->validate([
            'company' => 'required|string|max:120',
            'industry' => 'required|in:' . implode(',', array_keys(config('signo.industries'))),
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email,' . $user->id,
        ]);
        $this->org()->update(['name' => $this->company, 'industry' => $this->industry]);
        $user->update(['name' => $this->name, 'email' => $this->email]);
        $this->saved = 'profile';
    }

    public function savePassword(): void
    {
        $this->validate(['currentPassword' => 'required', 'newPassword' => 'required|min:8'], ['newPassword.min' => 'Mindestens 8 Zeichen.']);
        if (! Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->addError('currentPassword', 'Das aktuelle Passwort stimmt nicht.');
            return;
        }
        auth()->user()->update(['password' => $this->newPassword]);
        $this->reset('currentPassword', 'newPassword');
        $this->saved = 'password';
    }

    public function checkout(string $plan)
    {
        $this->billingError = null;
        abort_unless(array_key_exists($plan, config('signo.plans')), 404);
        try {
            return $this->redirect(Stripe::checkoutUrl($this->org(), $plan, auth()->user()->email));
        } catch (\Throwable $e) {
            report($e);
            $this->billingError = 'Die Bezahlung konnte nicht gestartet werden. Bitte versuchen Sie es später erneut oder schreiben Sie uns.';
        }
    }

    public function portal()
    {
        try {
            return $this->redirect(Stripe::portalUrl($this->org()));
        } catch (\Throwable $e) {
            report($e);
            $this->billingError = 'Das Kundenportal ist gerade nicht erreichbar.';
        }
    }

    public function deleteAccount()
    {
        if (! Hash::check($this->deletePassword, auth()->user()->password)) {
            $this->addError('deletePassword', 'Passwort stimmt nicht.');
            return;
        }
        $org = $this->org();
        if ($org->stripe_subscription_id && Stripe::enabled()) {
            try { Stripe::delete('subscriptions/' . $org->stripe_subscription_id); } catch (\Throwable $e) { report($e); }
        }
        Storage::disk('public')->deleteDirectory('org/' . $org->id);
        $org->screens()->delete();
        $org->users()->delete();
        $org->delete();
        Auth::logout();
        session()->invalidate();
        return $this->redirect('/', navigate: false);
    }

    public function render()
    {
        return view('livewire.account', [
            'org' => $this->org(),
            'stripe' => Stripe::enabled(),
        ]);
    }
}
