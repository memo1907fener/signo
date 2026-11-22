<div class="card p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight">Passwort vergessen</h1>
    @if ($sent)
        <p class="mt-4 text-sm text-muted">Falls ein Konto mit dieser Adresse existiert, haben wir Ihnen soeben einen Link geschickt. Bitte prüfen Sie auch den Spam-Ordner.</p>
    @else
        <p class="mt-1 text-sm text-muted">Wir schicken Ihnen einen Link zum Zurücksetzen.</p>
        <form wire:submit="send" class="mt-6 space-y-5">
            <div>
                <label class="label" for="email">E-Mail-Adresse</label>
                <input id="email" type="email" wire:model="email" class="input" autofocus>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>
            <button class="btn-primary w-full py-3">Link senden</button>
        </form>
    @endif
    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="text-muted hover:text-ink">← Zur Anmeldung</a></p>
</div>
