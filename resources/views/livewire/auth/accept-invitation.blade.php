<div class="card p-6 sm:p-8">
    @if (! $inv)
        <h1 class="text-2xl font-bold tracking-tight">{{ __('Einladung nicht gültig') }}</h1>
        <p class="mt-2 text-sm text-muted">{{ __('Der Link ist abgelaufen oder wurde schon verwendet. Bitten Sie um eine neue Einladung.') }}</p>
    @else
        <h1 class="text-2xl font-bold tracking-tight">Einladung zu „{{ $inv->organization->name }}“</h1>
        <p class="mt-2 text-sm text-muted">{{ __(':name lädt Sie ein, als :role bei Signo mitzuarbeiten.', ['name' => $inv->inviter?->name ?? __('Jemand'), 'role' => __($inv->role === 'owner' ? 'Inhaber' : 'Mitarbeiter')]) }}</p>

        @auth
            <p class="mt-6 text-sm">{{ __('Angemeldet als') }} <strong>{{ auth()->user()->email }}</strong>.</p>
            <button wire:click="accept" class="btn-brand mt-4 w-full py-3">{{ __('Einladung annehmen') }}</button>
        @else
            @if ($existing)
                <p class="mt-6 text-sm">{!! __('Für :email gibt es schon ein Konto. Bitte melden Sie sich an – danach nehmen Sie die Einladung an.', ['email' => '<strong>' . e($inv->email) . '</strong>']) !!}</p>
                <a href="{{ route('login') }}" class="btn-primary mt-4 w-full py-3">{{ __('Anmelden') }}</a>
            @else
                <form wire:submit="accept" class="mt-6 space-y-5">
                    <div>
                        <span class="label">{{ __('E-Mail-Adresse') }}</span>
                        <p class="input bg-paper">{{ $inv->email }}</p>
                    </div>
                    <div>
                        <label class="label" for="name">{{ __('Ihr Name') }}</label>
                        <input id="name" wire:model="name" class="input" autocomplete="name">
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="password">{{ __('Passwort wählen') }}</label>
                        <input id="password" type="password" wire:model="password" class="input" autocomplete="new-password">
                        @error('password') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <button class="btn-brand w-full py-3">{{ __('Konto anlegen und beitreten') }}</button>
                </form>
            @endif
        @endauth
    @endif
</div>
