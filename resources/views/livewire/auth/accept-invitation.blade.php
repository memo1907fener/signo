<div class="card p-6 sm:p-8">
    @if (! $inv)
        <h1 class="text-2xl font-bold tracking-tight">Einladung nicht gültig</h1>
        <p class="mt-2 text-sm text-muted">Der Link ist abgelaufen oder wurde schon verwendet. Bitten Sie um eine neue Einladung.</p>
    @else
        <h1 class="text-2xl font-bold tracking-tight">Einladung zu „{{ $inv->organization->name }}“</h1>
        <p class="mt-2 text-sm text-muted">{{ $inv->inviter?->name ?? 'Jemand' }} lädt Sie ein, als {{ $inv->role === 'owner' ? 'Inhaber' : 'Mitarbeiter' }} bei Signo mitzuarbeiten.</p>

        @auth
            <p class="mt-6 text-sm">Angemeldet als <strong>{{ auth()->user()->email }}</strong>.</p>
            <button wire:click="accept" class="btn-brand mt-4 w-full py-3">Einladung annehmen</button>
        @else
            @if ($existing)
                <p class="mt-6 text-sm">Für <strong>{{ $inv->email }}</strong> gibt es schon ein Konto. Bitte melden Sie sich an – danach nehmen Sie die Einladung an.</p>
                <a href="{{ route('login') }}" class="btn-primary mt-4 w-full py-3">Anmelden</a>
            @else
                <form wire:submit="accept" class="mt-6 space-y-5">
                    <div>
                        <span class="label">E-Mail-Adresse</span>
                        <p class="input bg-paper">{{ $inv->email }}</p>
                    </div>
                    <div>
                        <label class="label" for="name">Ihr Name</label>
                        <input id="name" wire:model="name" class="input" autocomplete="name">
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="password">Passwort wählen</label>
                        <input id="password" type="password" wire:model="password" class="input" autocomplete="new-password">
                        @error('password') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <button class="btn-brand w-full py-3">Konto anlegen und beitreten</button>
                </form>
            @endif
        @endauth
    @endif
</div>
