<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-bold tracking-tight">Konto</h1>
        <p class="mt-1 text-muted">{{ $org->name }} · {{ $org->planName() }}</p>
    </div>

    {{-- Paket --}}
    @if ($isOwner)
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
    @endif

    {{-- Standorte --}}
    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">Standorte</h2>
        <p class="mt-1 text-sm text-muted">Jeder Standort hat eigene Seiten, Bildschirme und ein eigenes Paket.</p>
        <div class="mt-5 divide-y divide-black/5">
            @foreach ($locations as $loc)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="loc-{{ $loc->id }}">
                    <div>
                        <p class="font-semibold">{{ $loc->name }} @if ($loc->id === $org->id)<span class="chip ml-1 bg-ink text-white">aktuell</span>@endif</p>
                        <p class="text-xs text-muted">{{ $loc->planName() }} · {{ $loc->screens_count }} {{ $loc->screens_count === 1 ? 'Bildschirm' : 'Bildschirme' }} · {{ $loc->pivot->role === 'owner' ? 'Inhaber' : 'Mitarbeiter' }}</p>
                    </div>
                    @if ($loc->id !== $org->id)
                        <button wire:click="switchTo({{ $loc->id }})" class="btn-ghost btn-sm">Wechseln</button>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($isOwner)
            <form wire:submit="addLocation" class="mt-4 flex flex-wrap gap-2">
                <input wire:model="newLocation" class="input max-w-xs" placeholder="z. B. Filiale Graz">
                <button class="btn-primary">+ Standort anlegen</button>
            </form>
            @error('newLocation') <p class="error">{{ $message }}</p> @enderror
        @endif
    </section>

    {{-- Team --}}
    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">Team</h2>
        <p class="mt-1 text-sm text-muted">Mitarbeiter können Inhalte, Bildschirme und Bestellungen bearbeiten. Paket und Konto verwalten nur Inhaber.</p>
        <div class="mt-5 divide-y divide-black/5">
            @foreach ($members as $m)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="m-{{ $m->id }}">
                    <div>
                        <p class="font-semibold">{{ $m->name }} @if ($m->id === auth()->id())<span class="text-xs text-muted">(Sie)</span>@endif</p>
                        <p class="text-xs text-muted">{{ $m->email }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($isOwner && $m->id !== auth()->id())
                            <select wire:change="setRole({{ $m->id }}, $event.target.value)" class="input w-auto py-1.5 text-sm">
                                <option value="editor" @selected($m->pivot->role === 'editor')>Mitarbeiter</option>
                                <option value="owner" @selected($m->pivot->role === 'owner')>Inhaber</option>
                            </select>
                            <button wire:click="removeMember({{ $m->id }})" wire:confirm="{{ $m->name }} aus dem Team entfernen?" class="btn-ghost btn-sm text-red-600">Entfernen</button>
                        @else
                            <span class="chip bg-black/5 text-muted">{{ $m->pivot->role === 'owner' ? 'Inhaber' : 'Mitarbeiter' }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
            @foreach ($invitations as $inv)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="inv-{{ $inv->id }}">
                    <div>
                        <p class="font-semibold text-muted">{{ $inv->email }}</p>
                        <p class="text-xs text-muted">Eingeladen · {{ $inv->role === 'owner' ? 'Inhaber' : 'Mitarbeiter' }}</p>
                    </div>
                    @if ($isOwner)
                        <div class="flex gap-2">
                            <button x-data @click="navigator.clipboard.writeText('{{ $inv->url() }}'); $el.textContent = 'Kopiert ✓'" class="btn-ghost btn-sm">Link kopieren</button>
                            <button wire:click="revokeInvitation({{ $inv->id }})" class="btn-ghost btn-sm text-red-600">Zurückziehen</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($isOwner)
            <form wire:submit="invite" class="mt-4 flex flex-wrap gap-2">
                <input type="email" wire:model="inviteEmail" class="input max-w-xs" placeholder="E-Mail-Adresse">
                <select wire:model="inviteRole" class="input w-auto">
                    <option value="editor">Mitarbeiter</option>
                    <option value="owner">Inhaber</option>
                </select>
                <button class="btn-primary">Einladen</button>
            </form>
            @error('inviteEmail') <p class="error">{{ $message }}</p> @enderror
            @if ($inviteLink)
                <p class="mt-3 rounded-xl bg-green-50 px-3 py-2 text-sm text-green-800">Einladung erstellt. Falls keine E-Mail ankommt, diesen Link schicken: <span class="break-all font-mono text-xs">{{ $inviteLink }}</span></p>
            @endif
        @endif
    </section>

    {{-- Profil --}}
    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">Betrieb & Zugang</h2>
        <form wire:submit="saveProfile" class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><label class="label">Name des Betriebs</label><input wire:model="company" class="input" @disabled(! $isOwner)>@error('company')<p class="error">{{ $message }}</p>@enderror</div>
            <div>
                <label class="label">Branche</label>
                <select wire:model="industry" class="input" @disabled(! $isOwner)>
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

    @if ($isOwner)
    <section class="card p-6 sm:p-8" x-data="{ open: false }">
        <h2 class="text-xl font-bold">{{ $locations->count() > 1 ? 'Standort löschen' : 'Konto löschen' }}</h2>
        <p class="mt-1 text-sm text-muted">Löscht „{{ $org->name }}“ mit allen Seiten, Bildern, Bildschirmen und Bestellungen endgültig. Ein laufendes Abo wird sofort beendet.</p>
        <button x-show="!open" @click="open = true" class="btn-danger mt-4">Konto löschen …</button>
        <form x-show="open" x-cloak wire:submit="deleteAccount" class="mt-4 flex flex-wrap items-start gap-3">
            <div>
                <input type="password" wire:model="deletePassword" class="input w-64" placeholder="Passwort zur Bestätigung">
                @error('deletePassword')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-danger">Endgültig löschen</button>
        </form>
    </section>
    @endif
</div>
