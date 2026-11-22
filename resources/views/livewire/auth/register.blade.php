<div class="card p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight">{{ config('signo.trial_days') }} Tage kostenlos testen</h1>
    <p class="mt-1 text-sm text-muted">Ohne Kreditkarte. Endet automatisch.</p>

    <form wire:submit="register" class="mt-6 space-y-5">
        <div>
            <span class="label">Ihre Branche</span>
            <div class="grid grid-cols-2 gap-2">
                @foreach (config('signo.industries') as $key => $ind)
                    <label class="flex cursor-pointer items-center gap-2 rounded-2xl px-3 py-2.5 text-sm ring-1 transition {{ $industry === $key ? 'bg-ink text-white ring-ink' : 'bg-white ring-black/10 hover:ring-black/25' }}">
                        <input type="radio" wire:model.live="industry" value="{{ $key }}" class="sr-only">
                        <span>{{ $ind['icon'] }}</span><span class="font-medium leading-tight">{{ $ind['label'] }}</span>
                    </label>
                @endforeach
            </div>
            @error('industry') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="company">Name Ihres Betriebs</label>
            <input id="company" wire:model="company" class="input" placeholder="z. B. Café Sonnenschein" autocomplete="organization">
            @error('company') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="name">Ihr Name</label>
            <input id="name" wire:model="name" class="input" autocomplete="name">
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="email">E-Mail-Adresse</label>
            <input id="email" type="email" wire:model="email" class="input" autocomplete="email">
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="password">Passwort</label>
            <input id="password" type="password" wire:model="password" class="input" autocomplete="new-password">
            <p class="hint">Mindestens 8 Zeichen</p>
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="absolute -left-[9999px]" aria-hidden="true">
            <label>Webseite <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <label class="flex items-start gap-2.5 text-sm text-muted">
            <input type="checkbox" wire:model="terms" class="mt-0.5 h-4 w-4 rounded accent-ink">
            <span>Ich akzeptiere die <a href="{{ route('legal', 'agb') }}" target="_blank" class="underline">AGB</a> und habe die <a href="{{ route('legal', 'datenschutz') }}" target="_blank" class="underline">Datenschutzerklärung</a> gelesen.</span>
        </label>
        @error('terms') <p class="error">{{ $message }}</p> @enderror

        <button class="btn-brand w-full py-3 text-base" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register">Kostenlos starten</span>
            <span wire:loading wire:target="register">Einen Moment …</span>
        </button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">Schon registriert? <a href="{{ route('login') }}" class="font-semibold text-ink hover:underline">Anmelden</a></p>
</div>
