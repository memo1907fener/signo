<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-bold tracking-tight">Konto</h1>
        <p class="mt-1 text-muted">{{ $org->name }} · {{ $org->planName() }}</p>
    </div>

    {{-- Paket --}}
    <section id="paket" class="card p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold">Ihr Paket</h2>
                @if ($org->hasSubscription())
                    <p class="mt-1 text-muted">
                        <strong class="text-ink">{{ $org->planName() }}</strong>
                        @if ($org->subscription_ends_at) – gekündigt, läuft bis {{ $org->subscription_ends_at->format('d.m.Y') }}
                        @elseif ($org->subscription_status === 'past_due') – <span class="text-red-600">Zahlung offen</span>
                        @else – aktiv @endif
                    </p>
                @elseif ($org->onTrial())
                    <p class="mt-1 text-muted">Testphase bis {{ $org->trial_ends_at->format('d.m.Y') }}. Danach einfach ein Paket wählen – monatlich kündbar.</p>
                @else
                    <p class="mt-1 text-red-600">Kein aktives Paket – Bildschirme und QR-Seiten sind pausiert.</p>
                @endif
            </div>
            @if ($org->stripe_customer_id && $stripe)
                <button wire:click="portal" class="btn-ghost">Rechnungen & Zahlungsart</button>
            @endif
        </div>

        @if (! $org->hasSubscription())
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach (config('signo.plans') as $key => $p)
                    <div class="flex flex-col rounded-3xl p-5 ring-1 {{ $key === 'screen' ? 'bg-ink text-white ring-ink' : 'bg-paper ring-black/5' }}">
                        <p class="font-semibold">{{ $p['name'] }}</p>
                        <p class="mt-2"><span class="text-3xl font-bold">{{ number_format($p['price'] / 100, 0, ',', '.') }} €</span>
                            <span class="text-sm opacity-70">/ {{ $p['interval'] === 'year' ? 'Jahr' : 'Monat' }}</span></p>
                        <ul class="mt-4 flex-1 space-y-1.5 text-sm opacity-90">
                            @foreach ($p['features'] as $f) <li>✓ {{ $f }}</li> @endforeach
                        </ul>
                        @if ($stripe)
                            <button wire:click="checkout('{{ $key }}')" class="mt-5 {{ $key === 'screen' ? 'btn-brand' : 'btn-primary' }}">Auswählen</button>
                        @endif
                    </div>
                @endforeach
            </div>
            @if (! $stripe)
                <p class="mt-4 text-sm text-muted">Die Online-Bezahlung wird gerade eingerichtet. Schreiben Sie uns an <a class="underline" href="mailto:{{ config('signo.company.email') }}">{{ config('signo.company.email') }}</a>.</p>
            @endif
            <p class="mt-4 text-xs text-muted">Alle Preise inkl. USt. Bezahlung mit Karte, SEPA oder Apple/Google Pay.</p>
        @endif
        @if ($billingError) <p class="error mt-4">{{ $billingError }}</p> @endif
    </section>

    {{-- Profil --}}
    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">Betrieb & Zugang</h2>
        <form wire:submit="saveProfile" class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><label class="label">Name des Betriebs</label><input wire:model="company" class="input">@error('company')<p class="error">{{ $message }}</p>@enderror</div>
            <div>
                <label class="label">Branche</label>
                <select wire:model="industry" class="input">
                    @foreach (config('signo.industries') as $k => $i)
                        <option value="{{ $k }}">{{ $i['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="label">Ihr Name</label><input wire:model="name" class="input">@error('name')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">E-Mail-Adresse</label><input type="email" wire:model="email" class="input">@error('email')<p class="error">{{ $message }}</p>@enderror</div>
            <div class="flex items-center gap-3 sm:col-span-2">
                <button class="btn-primary">Speichern</button>
                @if ($saved === 'profile') <span class="text-sm text-green-700">Gespeichert</span> @endif
            </div>
        </form>
    </section>

    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">Passwort ändern</h2>
        <form wire:submit="savePassword" class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><label class="label">Aktuelles Passwort</label><input type="password" wire:model="currentPassword" class="input" autocomplete="current-password">@error('currentPassword')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">Neues Passwort</label><input type="password" wire:model="newPassword" class="input" autocomplete="new-password">@error('newPassword')<p class="error">{{ $message }}</p>@enderror</div>
            <div class="flex items-center gap-3 sm:col-span-2">
                <button class="btn-primary">Passwort ändern</button>
                @if ($saved === 'password') <span class="text-sm text-green-700">Geändert</span> @endif
            </div>
        </form>
    </section>

    <section class="card p-6 sm:p-8" x-data="{ open: false }">
        <h2 class="text-xl font-bold">Konto löschen</h2>
        <p class="mt-1 text-sm text-muted">Löscht alle Seiten, Bilder und Bildschirme endgültig. Ein laufendes Abo wird sofort beendet.</p>
        <button x-show="!open" @click="open = true" class="btn-danger mt-4">Konto löschen …</button>
        <form x-show="open" x-cloak wire:submit="deleteAccount" class="mt-4 flex flex-wrap items-start gap-3">
            <div>
                <input type="password" wire:model="deletePassword" class="input w-64" placeholder="Passwort zur Bestätigung">
                @error('deletePassword')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-danger">Endgültig löschen</button>
        </form>
    </section>
</div>
