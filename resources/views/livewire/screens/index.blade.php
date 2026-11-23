<div wire:poll.30s>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ __('Bildschirme') }}</h1>
            <p class="mt-1 text-muted">{{ trans_choice(':used von :n Bildschirm in Ihrem Paket.|:used von :n Bildschirmen in Ihrem Paket.', $org->screenLimit(), ['used' => $screens->count(), 'n' => $org->screenLimit()]) }}</p>
        </div>
        <button wire:click="$set('adding', true)" class="btn-brand">{{ __('+ Bildschirm verbinden') }}</button>
    </div>

    @if ($adding || $screens->isEmpty())
        <div class="card mt-6 grid gap-8 p-6 sm:p-8 md:grid-cols-2">
            <div>
                <h2 class="text-lg font-bold">{{ __('So verbinden Sie einen Bildschirm') }}</h2>
                <ol class="mt-4 space-y-4 text-sm">
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">1</span>
                        <span>{!! __('Am Fernseher oder an der TV-Box den Browser öffnen und :host eingeben – oder die Signo-App für Android starten.', ['host' => '<strong class="whitespace-nowrap">' . e(str_replace(['https://', 'http://'], '', url('/tv'))) . '</strong>']) !!}</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">2</span>
                        <span>{{ __('Auf dem Bildschirm erscheint ein Code mit 6 Zeichen.') }}</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">3</span>
                        <span>{{ __('Code hier eingeben – fertig.') }}</span></li>
                </ol>
            </div>
            <form wire:submit="pair" class="space-y-4">
                <div>
                    <label class="label" for="code">{{ __('Code vom Bildschirm') }}</label>
                    <input id="code" wire:model="code" class="input text-center font-mono text-2xl uppercase tracking-[.3em]" maxlength="7" placeholder="{{ __('ABC123') }}" autocomplete="off">
                    @error('code') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="sname">{{ __('Name') }}</label>
                    <input id="sname" wire:model="name" class="input" placeholder="{{ __('z. B. Fenster, Theke, Wartezimmer') }}">
                </div>
                <button class="btn-primary w-full py-3">{{ __('Verbinden') }}</button>
            </form>
        </div>
    @endif

    {{-- Eilmeldung --}}
    @php $notice = $org->activeNotice(); @endphp
    <div class="card mt-6 p-5 sm:p-6 {{ $notice ? 'ring-2 ring-red-500' : '' }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold">📣 {{ __('Eilmeldung') }}</h2>
                <p class="text-sm text-muted">{{ __('Erscheint innerhalb von 30 Sekunden auf allen Bildschirmen und auf den Handy-Seiten.') }}</p>
            </div>
            @if ($notice)
                <button wire:click="clearNotice" class="btn-danger btn-sm">{{ __('Meldung beenden') }}</button>
            @endif
        </div>
        @if ($notice)
            <div class="mt-4 rounded-2xl bg-red-600 px-5 py-4 text-lg font-bold text-white">{{ $notice['text'] }}</div>
            <p class="hint mt-2">{{ $notice['until'] ? __('Läuft bis :time Uhr.', ['time' => \Illuminate\Support\Carbon::parse($notice['until'])->timezone(config('app.timezone'))->format('H:i')]) : __('Läuft, bis Sie sie beenden.') }}</p>
        @else
            <form wire:submit="sendNotice" class="mt-4 space-y-3">
                <input wire:model="noticeText" class="input" maxlength="160" placeholder="{{ __('z. B. Heute wegen Krankheit geschlossen') }}">
                @error('noticeText') <p class="error">{{ $message }}</p> @enderror
                <div class="flex flex-wrap items-center gap-3">
                    <select wire:model="noticeFor" class="input w-auto py-2 text-sm">
                        <option value="60">{{ __('1 Stunde') }}</option>
                        <option value="today">{{ __('Bis heute Abend') }}</option>
                        <option value="open">{{ __('Bis ich sie beende') }}</option>
                    </select>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="noticeFull" class="h-4 w-4 accent-ink"> {{ __('Ganzer Bildschirm') }}</label>
                    <button class="btn-primary ml-auto">{{ __('Jetzt zeigen') }}</button>
                </div>
            </form>
        @endif
    </div>

    @if ($screens->isNotEmpty())
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($screens as $s)
                @php $on = $s->isOnline(); $sleep = $on && $s->isSleeping(); @endphp
                <a href="{{ route('screens.edit', $s) }}" wire:navigate class="card block overflow-hidden transition hover:ring-black/20" wire:key="s-{{ $s->id }}">
                    <div class="flex aspect-video items-center justify-center bg-ink/95">
                        @if ($s->screenshot_path)
                            <img src="{{ \App\Models\Page::fileUrl($s->screenshot_path) }}?t={{ $s->screenshot_at?->timestamp }}" class="h-full w-full object-cover" alt="">
                        @else
                            <span class="text-5xl opacity-60">🖥️</span>
                        @endif
                    </div>
                    <div class="p-5">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ ! $on ? 'bg-red-500' : ($sleep ? 'bg-amber-400' : 'bg-green-500') }}"></span>
                            <span class="font-semibold">{{ $s->name }}</span>
                        </div>
                        <p class="mt-1 text-sm text-muted">{{ $s->statusLabel() }} · {{ $s->hoursLabel() }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    @if ($screens->isNotEmpty())
        <div class="card mt-6 flex items-center justify-between gap-4 p-5">
            <div>
                <p class="font-semibold">🔔 {{ __('Ausfall-Alarm per E-Mail') }}</p>
                <p class="text-sm text-muted">{{ __('Sie bekommen eine E-Mail, wenn ein Bildschirm während der Betriebszeit länger als :n Minuten offline ist – und eine Entwarnung, sobald er wieder läuft.', ['n' => config('signo.offline_minutes')]) }}</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" wire:click="toggleAlerts" @checked($org->offline_alerts) class="peer sr-only">
                <span class="h-7 w-12 rounded-full bg-black/15 transition peer-checked:bg-ink"></span>
                <span class="absolute left-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
            </label>
        </div>
    @endif
</div>
