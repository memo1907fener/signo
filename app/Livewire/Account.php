<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithOrg;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Support\Starter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    // Team
    public string $inviteEmail = '';
    public string $inviteRole = 'editor';
    public ?string $inviteLink = null;

    // Standorte
    public string $newLocation = '';

    public function mount(): void
    {
        $org = $this->org();
        $this->company = $org->name;
        $this->industry = $org->industry;
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
        if (request('bezahlt')) session()->flash('ok', 'Danke! Ihr Paket ist aktiv. Die Bestätigung kommt per E-Mail.');
    }

    protected function ownerOnly(): void
    {
        abort_unless(auth()->user()->isOwnerOf($this->org()), 403);
    }

    // ---------- Team ----------
    public function invite(): void
    {
        $this->ownerOnly();
        $this->inviteEmail = strtolower(trim($this->inviteEmail));
        $this->validate(['inviteEmail' => 'required|email|max:190', 'inviteRole' => 'required|in:owner,editor'], ['inviteEmail.required' => 'Bitte eine E-Mail-Adresse eingeben.']);
        $org = $this->org();
        if ($org->users()->where('email', $this->inviteEmail)->exists()) {
            $this->addError('inviteEmail', 'Diese Person gehört bereits zum Team.');
            return;
        }
        if ($org->invitations()->whereNull('accepted_at')->count() >= 20) {
            $this->addError('inviteEmail', 'Zu viele offene Einladungen.');
            return;
        }
        $inv = Invitation::updateOrCreate(
            ['organization_id' => $org->id, 'email' => $this->inviteEmail, 'accepted_at' => null],
            ['role' => $this->inviteRole, 'token' => Str::random(40), 'invited_by' => auth()->id()]
        );
        $this->inviteLink = $inv->url();
        try {
            $name = auth()->user()->name;
            Mail::raw("Hallo,\n\n$name lädt Sie ein, bei Signo für „{$org->name}“ mitzuarbeiten.\n\nEinladung annehmen:\n{$inv->url()}\n\nViele Grüße\nIhr Signo-Team",
                fn ($m) => $m->to($inv->email)->subject("Einladung zu {$org->name} bei Signo"));
        } catch (\Throwable $e) {
            report($e);
        }
        $this->reset('inviteEmail');
    }

    public function revokeInvitation(int $id): void
    {
        $this->ownerOnly();
        $this->org()->invitations()->whereKey($id)->delete();
    }

    public function removeMember(int $userId): void
    {
        $this->ownerOnly();
        abort_if($userId === auth()->id(), 400);
        $org = $this->org();
        $org->users()->detach($userId);
        if ($org->owners()->count() === 0) {
            $org->users()->updateExistingPivot(auth()->id(), ['role' => 'owner']);
        }
    }

    public function setRole(int $userId, string $role): void
    {
        $this->ownerOnly();
        abort_unless(in_array($role, ['owner', 'editor'], true), 400);
        $org = $this->org();
        if ($role === 'editor' && $org->owners()->count() <= 1 && $org->owners()->where('users.id', $userId)->exists()) return;
        $org->users()->updateExistingPivot($userId, ['role' => $role]);
    }

    // ---------- Standorte ----------
    public function switchTo(int $orgId)
    {
        $org = auth()->user()->organizations()->where('organizations.id', $orgId)->firstOrFail();
        auth()->user()->forceFill(['organization_id' => $org->id])->save();
        session()->flash('ok', 'Gewechselt zu „' . $org->name . '“.');
        return $this->redirectRoute('pages.index', navigate: false);
    }

    public function addLocation()
    {
        $this->ownerOnly();
        $this->validate(['newLocation' => 'required|string|max:120'], ['newLocation.required' => 'Bitte einen Namen für den Standort eingeben.']);
        $user = auth()->user();
        if ($user->organizations()->count() >= 25) {
            $this->addError('newLocation', 'Maximal 25 Standorte pro Konto.');
            return;
        }
        $current = $this->org();
        $loc = Organization::create([
            'name' => trim($this->newLocation),
            'industry' => $current->industry,
            // Kein neuer Gratis-Zeitraum: gleiche Testphase wie der aktuelle Betrieb
            'trial_ends_at' => $current->trial_ends_at,
            'signup_ip' => request()->ip(),
        ]);
        Starter::create($loc);
        $loc->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['organization_id' => $loc->id])->save();
        session()->flash('ok', 'Standort „' . $loc->name . '“ angelegt. Sie arbeiten jetzt dort.');
        return $this->redirectRoute('pages.index', navigate: false);
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
        if ($user->isOwnerOf($this->org())) {
            $this->org()->update(['name' => $this->company, 'industry' => $this->industry]);
        }
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
        $this->ownerOnly();
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
        $this->ownerOnly();
        try {
            return $this->redirect(Stripe::portalUrl($this->org()));
        } catch (\Throwable $e) {
            report($e);
            $this->billingError = 'Das Kundenportal ist gerade nicht erreichbar.';
        }
    }

    public function deleteAccount()
    {
        $this->ownerOnly();
        if (! Hash::check($this->deletePassword, auth()->user()->password)) {
            $this->addError('deletePassword', 'Passwort stimmt nicht.');
            return;
        }
        $org = $this->org();
        if ($org->stripe_subscription_id && Stripe::enabled()) {
            try { Stripe::delete('subscriptions/' . $org->stripe_subscription_id); } catch (\Throwable $e) { report($e); }
        }
        $userId = auth()->id();
        $org->deleteCompletely();

        if (User::find($userId)) {
            session()->flash('ok', 'Der Standort wurde gelöscht.');
            return $this->redirectRoute('pages.index', navigate: false);
        }
        Auth::logout();
        session()->invalidate();
        return $this->redirect('/', navigate: false);
    }

    public function render()
    {
        $org = $this->org();
        $user = auth()->user();
        return view('livewire.account', [
            'org' => $org,
            'stripe' => Stripe::enabled(),
            'isOwner' => $user->isOwnerOf($org),
            'members' => $org->users()->orderBy('name')->get(),
            'invitations' => $org->invitations()->whereNull('accepted_at')->latest()->get(),
            'locations' => $user->organizations()->withCount('screens')->get(),
        ]);
    }
}
