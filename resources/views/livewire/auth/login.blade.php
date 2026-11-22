<div class="card p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight">Willkommen zurück</h1>
    <form wire:submit="login" class="mt-6 space-y-5">
        <div>
            <label class="label" for="email">E-Mail-Adresse</label>
            <input id="email" type="email" wire:model="email" class="input" autocomplete="email" autofocus>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label class="label" for="password">Passwort</label>
                <a href="{{ route('password.request') }}" class="mb-1.5 text-xs text-muted hover:text-ink">Vergessen?</a>
            </div>
            <input id="password" type="password" wire:model="password" class="input" autocomplete="current-password">
        </div>
        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" wire:model="remember" class="h-4 w-4 accent-ink"> Angemeldet bleiben
        </label>
        <button class="btn-primary w-full py-3 text-base" wire:loading.attr="disabled">Anmelden</button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">Neu bei Signo? <a href="{{ route('register') }}" class="font-semibold text-ink hover:underline">Kostenlos testen</a></p>
</div>
