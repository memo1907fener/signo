<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-bold tracking-tight">{{ __('Konto') }}</h1>
        <p class="mt-1 text-muted">{{ $org->name }} · {{ $org->planName() }}</p>
    </div>

    {{-- Paket --}}
    @if ($isOwner)
    <section id="paket" class="card p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold">{{ __('Ihr Paket') }}</h2>
                @if ($org->hasSubscription())
                    <p class="mt-1 text-muted">
                        <strong class="text-ink">{{ $org->planName() }}</strong>
                        @if ($org->subscription_ends_at) – gekündigt, läuft bis {{ $org->subscription_ends_at->format('d.m.Y') }}
                        @elseif ($org->subscription_status === 'past_due') – <span class="text-red-600">{{ __('Zahlung offen') }}</span>
                        @else – {{ __('aktiv') }} @endif
                    </p>
                @elseif ($org->onTrial())
                    <p class="mt-1 text-muted">{{ __('Testphase bis :date. Danach einfach ein Paket wählen – monatlich kündbar.', ['date' => $org->trial_ends_at->format('d.m.Y')]) }}</p>
                @else
                    <p class="mt-1 text-red-600">{{ __('Kein aktives Paket – Bildschirme und QR-Seiten sind pausiert.') }}</p>
                @endif
            </div>
            @if ($org->stripe_customer_id && $stripe)
                <button wire:click="portal" class="btn-ghost">{{ __('Rechnungen & Zahlungsart') }}</button>
            @endif
        </div>

        @if (! $org->hasSubscription())
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach (config('signo.plans') as $key => $p)
                    <div class="flex flex-col rounded-3xl p-5 ring-1 {{ $key === 'screen' ? 'bg-ink text-white ring-ink' : 'bg-paper ring-black/5' }}">
                        <p class="font-semibold">{{ __($p['name']) }}</p>
                        <p class="mt-2"><span class="text-3xl font-bold">{{ number_format($p['price'] / 100, 0, ',', '.') }} €</span>
                            <span class="text-sm opacity-70">/ {{ __($p['interval'] === 'year' ? 'Jahr' : 'Monat') }}</span></p>
                        <ul class="mt-4 flex-1 space-y-1.5 text-sm opacity-90">
                            @foreach ($p['features'] as $f) <li>✓ {{ __($f) }}</li> @endforeach
                        </ul>
                        @if ($stripe)
                            <button wire:click="checkout('{{ $key }}')" class="mt-5 {{ $key === 'screen' ? 'btn-brand' : 'btn-primary' }}">{{ __('Auswählen') }}</button>
                        @endif
                    </div>
                @endforeach
            </div>
            @if (! $stripe)
                <p class="mt-4 text-sm text-muted">{{ __('Die Online-Bezahlung wird gerade eingerichtet. Schreiben Sie uns an') }} <a class="underline" href="mailto:{{ config('signo.company.email') }}">{{ config('signo.company.email') }}</a>.</p>
            @endif
            <p class="mt-4 text-xs text-muted">{{ __('Alle Preise inkl. USt. Bezahlung mit Karte, SEPA oder Apple/Google Pay.') }}</p>
        @endif
        @if ($billingError) <p class="error mt-4">{{ $billingError }}</p> @endif
    </section>
    @endif

    {{-- Standorte --}}
    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">{{ __('Standorte') }}</h2>
        <p class="mt-1 text-sm text-muted">{{ __('Jeder Standort hat eigene Seiten, Bildschirme und ein eigenes Paket.') }}</p>
        <div class="mt-5 divide-y divide-black/5">
            @foreach ($locations as $loc)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="loc-{{ $loc->id }}">
                    <div>
                        <p class="font-semibold">{{ $loc->name }} @if ($loc->id === $org->id)<span class="chip ml-1 bg-ink text-white">{{ __('aktuell') }}</span>@endif</p>
                        <p class="text-xs text-muted">{{ __($loc->planName()) }} · {{ trans_choice(':n Bildschirm|:n Bildschirme', $loc->screens_count, ['n' => $loc->screens_count]) }} · {{ __($loc->pivot->role === 'owner' ? 'Inhaber' : 'Mitarbeiter') }}</p>
                    </div>
                    @if ($loc->id !== $org->id)
                        <button wire:click="switchTo({{ $loc->id }})" class="btn-ghost btn-sm">{{ __('Wechseln') }}</button>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($isOwner)
            <form wire:submit="addLocation" class="mt-4 flex flex-wrap gap-2">
                <input wire:model="newLocation" class="input max-w-xs" placeholder="{{ __('z. B. Filiale Graz') }}">
                <button class="btn-primary">{{ __('+ Standort anlegen') }}</button>
            </form>
            @error('newLocation') <p class="error">{{ $message }}</p> @enderror
        @endif
    </section>

    {{-- Team --}}
    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">{{ __('Team') }}</h2>
        <p class="mt-1 text-sm text-muted">{{ __('Mitarbeiter können Inhalte, Bildschirme und Bestellungen bearbeiten. Paket und Konto verwalten nur Inhaber.') }}</p>
        <div class="mt-5 divide-y divide-black/5">
            @foreach ($members as $m)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="m-{{ $m->id }}">
                    <div>
                        <p class="font-semibold">{{ $m->name }} @if ($m->id === auth()->id())<span class="text-xs text-muted">{{ __('(Sie)') }}</span>@endif</p>
                        <p class="text-xs text-muted">{{ $m->email }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($isOwner && $m->id !== auth()->id())
                            <select wire:change="setRole({{ $m->id }}, $event.target.value)" class="input w-auto py-1.5 text-sm">
                                <option value="editor" @selected($m->pivot->role === 'editor')>{{ __('Mitarbeiter') }}</option>
                                <option value="owner" @selected($m->pivot->role === 'owner')>{{ __('Inhaber') }}</option>
                            </select>
                            <button wire:click="removeMember({{ $m->id }})" wire:confirm="{{ __(':name aus dem Team entfernen?', ['name' => $m->name]) }}" class="btn-ghost btn-sm text-red-600">{{ __('Entfernen') }}</button>
                        @else
                            <span class="chip bg-black/5 text-muted">{{ __($m->pivot->role === 'owner' ? 'Inhaber' : 'Mitarbeiter') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
            @foreach ($invitations as $inv)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="inv-{{ $inv->id }}">
                    <div>
                        <p class="font-semibold text-muted">{{ $inv->email }}</p>
                        <p class="text-xs text-muted">{{ __('Eingeladen') }} · {{ __($inv->role === 'owner' ? 'Inhaber' : 'Mitarbeiter') }}</p>
                    </div>
                    @if ($isOwner)
                        <div class="flex gap-2">
                            <button x-data @click="navigator.clipboard.writeText('{{ $inv->url() }}'); $el.textContent = 'Kopiert ✓'" class="btn-ghost btn-sm">{{ __('Link kopieren') }}</button>
                            <button wire:click="revokeInvitation({{ $inv->id }})" class="btn-ghost btn-sm text-red-600">{{ __('Zurückziehen') }}</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($isOwner)
            <form wire:submit="invite" class="mt-4 flex flex-wrap gap-2">
                <input type="email" wire:model="inviteEmail" class="input max-w-xs" placeholder="{{ __('E-Mail-Adresse') }}">
                <select wire:model="inviteRole" class="input w-auto">
                    <option value="editor">{{ __('Mitarbeiter') }}</option>
                    <option value="owner">{{ __('Inhaber') }}</option>
                </select>
                <button class="btn-primary">{{ __('Einladen') }}</button>
            </form>
            @error('inviteEmail') <p class="error">{{ $message }}</p> @enderror
            @if ($inviteLink)
                <p class="mt-3 rounded-xl bg-green-50 px-3 py-2 text-sm text-green-800">{{ __('Einladung erstellt. Falls keine E-Mail ankommt, diesen Link schicken:') }} <span class="break-all font-mono text-xs">{{ $inviteLink }}</span></p>
            @endif
        @endif
    </section>

    {{-- Marke --}}
    <section id="marke" class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">{{ __('Marke') }}</h2>
        <p class="mt-1 text-sm text-muted">{{ __('Logo einmal hochladen – Signo übernimmt die Farbe für Ihre Seiten und zeigt das Logo am Bildschirm und am Handy.') }}</p>
        @php $brand = $org->brand ?? []; @endphp
        <div class="mt-6 grid gap-8 md:grid-cols-[220px_1fr]">
            <div>
                <label class="flex aspect-[4/3] cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-black/15 bg-paper p-4 text-center transition hover:border-ink {{ $isOwner ? '' : 'pointer-events-none opacity-60' }}">
                    <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="sr-only">
                    @if ($org->logoUrl())
                        <img src="{{ $org->logoUrl() }}" alt="" class="max-h-full max-w-full object-contain">
                    @else
                        <span class="text-3xl">🖼️</span>
                        <span class="mt-2 text-sm font-semibold">{{ __('Logo hochladen') }}</span>
                        <span class="text-xs text-muted">PNG, JPG, WEBP</span>
                    @endif
                </label>
                <p wire:loading wire:target="logo" class="hint">{{ __('Wird hochgeladen …') }}</p>
                @error('logo') <p class="error">{{ $message }}</p> @enderror
                @if ($org->logoUrl() && $isOwner)
                    <button wire:click="removeLogo" class="mt-2 text-xs text-muted hover:text-red-600">{{ __('Logo entfernen') }}</button>
                @endif
            </div>
            <div>
                <span class="label">{{ __('Markenfarbe') }}</span>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (($brand['colors'] ?? []) as $c)
                        <button wire:click="setBrandAccent('{{ $c }}')" @disabled(! $isOwner) class="h-10 w-10 rounded-full ring-offset-2 transition {{ strtolower($brandAccent) === $c ? 'ring-2 ring-ink' : 'hover:scale-110' }}" style="background: {{ $c }}" aria-label="{{ $c }}"></button>
                    @endforeach
                    <label class="flex h-10 cursor-pointer items-center gap-2 rounded-full bg-white px-3 text-sm ring-1 ring-black/10">
                        <input type="color" wire:model.live.debounce.600ms="brandAccent" class="h-6 w-6 cursor-pointer rounded-full border-0 bg-transparent p-0" @disabled(! $isOwner)>
                        <span>{{ __('Eigene Farbe') }}</span>
                    </label>
                </div>
                @error('brandAccent') <p class="error">{{ $message }}</p> @enderror
                <label class="mt-5 flex items-center gap-2.5 text-sm">
                    <input type="checkbox" wire:model.live="brandShowLogo" class="h-4 w-4 accent-ink" @disabled(! $isOwner)>
                    {{ __('Logo auf Bildschirm und Handy-Seite zeigen') }}
                </label>
                @if ($org->brandAccent() && $isOwner)
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <button wire:click="applyBrand" wire:confirm="{{ __('Die Markenfarbe auf alle Seiten anwenden?') }}" class="btn-primary btn-sm">{{ __('Auf alle Seiten anwenden') }}</button>
                        <span class="text-xs text-muted">{{ __('Bei einzelnen Seiten wählen Sie die Farbe „Marke“ unter „Aussehen“.') }}</span>
                    </div>
                @endif
                @if ($brandMessage) <p class="mt-3 text-sm text-green-700">{{ $brandMessage }}</p> @endif
            </div>
        </div>
    </section>

    {{-- Profil --}}
    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">{{ __('Betrieb & Zugang') }}</h2>
        <form wire:submit="saveProfile" class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><label class="label">{{ __('Name des Betriebs') }}</label><input wire:model="company" class="input" @disabled(! $isOwner)>@error('company')<p class="error">{{ $message }}</p>@enderror</div>
            <div>
                <label class="label">{{ __('Branche') }}</label>
                <select wire:model="industry" class="input" @disabled(! $isOwner)>
                    @foreach (config('signo.industries') as $k => $i)
                        <option value="{{ $k }}">{{ __($i['label']) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="label">{{ __('Ihr Name') }}</label><input wire:model="name" class="input">@error('name')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('E-Mail-Adresse') }}</label><input type="email" wire:model="email" class="input">@error('email')<p class="error">{{ $message }}</p>@enderror</div>
            <div>
                <label class="label">{{ __('Sprache · Language · Dil') }}</label>
                <select wire:model.live="locale" class="input">
                    @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3 sm:col-span-2">
                <button class="btn-primary">{{ __('Speichern') }}</button>
                @if ($saved === 'profile') <span class="text-sm text-green-700">{{ __('Gespeichert') }}</span> @endif
            </div>
        </form>
    </section>

    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">{{ __('Passwort ändern') }}</h2>
        <form wire:submit="savePassword" class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><label class="label">{{ __('Aktuelles Passwort') }}</label><input type="password" wire:model="currentPassword" class="input" autocomplete="current-password">@error('currentPassword')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label">{{ __('Neues Passwort') }}</label><input type="password" wire:model="newPassword" class="input" autocomplete="new-password">@error('newPassword')<p class="error">{{ $message }}</p>@enderror</div>
            <div class="flex items-center gap-3 sm:col-span-2">
                <button class="btn-primary">{{ __('Passwort ändern') }}</button>
                @if ($saved === 'password') <span class="text-sm text-green-700">{{ __('Geändert') }}</span> @endif
            </div>
        </form>
    </section>

    @if ($isOwner)
    <section class="card p-6 sm:p-8" x-data="{ open: false }">
        <h2 class="text-xl font-bold">{{ __($locations->count() > 1 ? 'Standort löschen' : 'Konto löschen') }}</h2>
        <p class="mt-1 text-sm text-muted">{{ __('Löscht „:name“ mit allen Seiten, Bildern, Bildschirmen und Bestellungen endgültig. Ein laufendes Abo wird sofort beendet.', ['name' => $org->name]) }}</p>
        <button x-show="!open" @click="open = true" class="btn-danger mt-4">{{ __('Konto löschen …') }}</button>
        <form x-show="open" x-cloak wire:submit="deleteAccount" class="mt-4 flex flex-wrap items-start gap-3">
            <div>
                <input type="password" wire:model="deletePassword" class="input w-64" placeholder="{{ __('Passwort zur Bestätigung') }}">
                @error('deletePassword')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-danger">{{ __('Endgültig löschen') }}</button>
        </form>
    </section>
    @endif
</div>
