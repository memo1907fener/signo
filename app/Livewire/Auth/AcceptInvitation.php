<?php

namespace App\Livewire\Auth;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Einladung')]
class AcceptInvitation extends Component
{
    #[Locked]
    public string $token = '';
    public string $name = '';
    public string $password = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        if (! Auth::check() && $this->invitation() && User::where('email', $this->invitation()->email)->exists()) {
            session()->put('url.intended', route('invitation', $token));
        }
    }

    protected function invitation(): ?Invitation
    {
        return Invitation::with('organization', 'inviter')->where('token', $this->token)->whereNull('accepted_at')
            ->where('created_at', '>', now()->subDays(14))->first();
    }

    public function accept()
    {
        $inv = $this->invitation();
        abort_unless($inv, 404);

        $user = Auth::user();
        if (! $user) {
            if (User::where('email', $inv->email)->exists()) {
                return $this->redirectRoute('login', navigate: false);
            }
            $this->validate(['name' => 'required|string|max:120', 'password' => 'required|string|min:8'], [
                'name.required' => __('Bitte Ihren Namen eingeben.'), 'password.min' => __('Mindestens 8 Zeichen.'),
            ]);
            $user = User::create(['name' => $this->name, 'email' => $inv->email, 'password' => $this->password, 'organization_id' => $inv->organization_id]);
            Auth::login($user, true);
            session()->regenerate();
        }

        $inv->organization->users()->syncWithoutDetaching([$user->id => ['role' => $inv->role]]);
        $user->forceFill(['organization_id' => $inv->organization_id])->save();
        $inv->update(['accepted_at' => now()]);
        session()->flash('ok', 'Willkommen bei „' . $inv->organization->name . '“!');
        return $this->redirectRoute('pages.index', navigate: false);
    }

    public function render()
    {
        $inv = $this->invitation();
        return view('livewire.auth.accept-invitation', [
            'inv' => $inv,
            'existing' => $inv ? User::where('email', $inv->email)->exists() : false,
        ]);
    }
}
