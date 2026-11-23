@php
    $accent = $org->brandAccent() ?? '#ff5a36';
    $title = trim((string) ($org->loyalty['title'] ?? '')) ?: $t['st_reward'];
    $reward = trim((string) ($org->loyalty['reward'] ?? ''));
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $org->name }}">
    <title>{{ $title }} · {{ $org->name }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
    <style>
        .cardbox { background: linear-gradient(160deg, {{ $accent }}, color-mix(in srgb, {{ $accent }} 62%, #000)); }
        .stamp { aspect-ratio: 1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px;
            border: 2px dashed rgba(255, 255, 255, .5); color: rgba(255, 255, 255, .55); transition: transform .35s cubic-bezier(.3, 1.6, .5, 1), background .3s; }
        .stamp.on { background: #fff; border: 2px solid #fff; color: {{ $accent }}; transform: rotate(-8deg); }
        .stamp.last { border-style: solid; }
        .go { background: {{ $accent }}; }
        @keyframes pop { 0% { transform: scale(.4) rotate(-30deg); } 100% { transform: rotate(-8deg); } }
        .stamp.fresh { animation: pop .5s cubic-bezier(.3, 1.6, .5, 1); }
    </style>
</head>
<body class="min-h-screen bg-paper font-sans text-ink">
<div class="mx-auto flex min-h-screen max-w-md flex-col px-5 py-8" x-data="signoCard({{ $card->stamps }}, {{ $goal }})">
    <div class="cardbox rounded-[28px] p-6 text-white shadow-xl">
        <div class="flex items-center justify-between gap-3">
            @if ($org->showsLogo())
                <img src="{{ $org->logoUrl() }}" alt="{{ $org->name }}" class="max-h-10 max-w-[150px] rounded-lg bg-white/90 object-contain p-1">
            @else
                <p class="text-sm font-semibold uppercase tracking-widest opacity-80">{{ $org->name }}</p>
            @endif
            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold" x-text="@js($t['st_of']).replace(':n', Math.min(stamps, goal)).replace(':goal', goal)"></span>
        </div>
        <h1 class="mt-4 text-2xl font-bold tracking-tight">{{ $title }}</h1>
        <div class="mt-5 grid grid-cols-5 gap-2.5">
            <template x-for="n in goal" :key="n">
                <div class="stamp" :class="{ on: n <= stamps, last: n === goal, fresh: n > fresh[0] && n <= fresh[1] }" x-text="n === goal && n > stamps ? '🎁' : (n <= stamps ? '✓' : n)"></div>
            </template>
        </div>
        @if ($reward !== '')
            <p class="mt-5 text-sm opacity-90">🎁 {{ $t['st_reward'] }}: <strong>{{ $reward }}</strong></p>
        @endif
    </div>

    <template x-if="stamps >= goal">
        <div class="mt-6 rounded-3xl bg-green-600 p-5 text-center text-white">
            <p class="text-lg font-bold">{{ $t['st_ready'] }}</p>
            <button type="button" class="mt-3 w-full rounded-2xl bg-white py-3.5 font-bold text-green-700" @click="open('redeem')">{{ $t['st_redeem'] }}</button>
        </div>
    </template>

    <button type="button" class="go mt-6 w-full rounded-2xl py-4 text-base font-bold text-white" @click="open('stamp')">{{ $t['st_get'] }}</button>
    <p class="mt-3 text-center text-sm font-semibold text-green-700" x-show="message" x-text="message"></p>
    <p class="mt-6 text-center text-xs text-muted">{{ $t['st_save'] }}</p>
    <p class="mt-auto pt-8 text-center text-xs text-muted"><a href="{{ url('/') }}">Signo</a></p>

    {{-- Eingabe für das Personal --}}
    <div class="fixed inset-0 z-40 bg-black/50" x-show="mode" x-cloak @click="mode = null"></div>
    <div class="fixed inset-x-0 bottom-0 z-50 mx-auto max-w-md rounded-t-[28px] bg-white p-6 pb-[calc(24px+env(safe-area-inset-bottom))]" x-show="mode" x-cloak>
        <p class="text-center text-sm text-muted">{{ $t['st_hand'] }}</p>
        <label class="label mt-4 text-center" for="pin">{{ $t['st_pin'] }}</label>
        <input id="pin" x-ref="pin" x-model="pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="off"
               class="input text-center text-3xl font-bold tracking-[.4em]" @keydown.enter="submit()">
        <div x-show="mode === 'stamp'" class="mt-4">
            <p class="label text-center">{{ $t['st_count'] }}</p>
            <div class="flex justify-center gap-2">
                <template x-for="n in 5" :key="n">
                    <button type="button" class="h-11 w-11 rounded-full text-sm font-bold ring-1 ring-black/10" :class="count === n ? 'bg-ink text-white' : 'bg-white'" @click="count = n" x-text="n"></button>
                </template>
            </div>
        </div>
        <p class="error mt-3 text-center" x-show="error" x-text="error"></p>
        <button type="button" class="go mt-5 w-full rounded-2xl py-4 font-bold text-white disabled:opacity-50" :disabled="busy || pin.length < 4" @click="submit()"
                x-text="mode === 'redeem' ? @js($t['st_redeem']) : @js($t['st_confirm'])"></button>
        <button type="button" class="mt-2 w-full py-3 text-sm text-muted" @click="mode = null">{{ $t['st_cancel'] }}</button>
    </div>
</div>
<script>
    function signoCard(stamps, goal) {
        return {
            stamps, goal, mode: null, pin: '', count: 1, busy: false, error: '', message: '', fresh: [0, 0],
            open(mode) { this.mode = mode; this.pin = ''; this.error = ''; this.count = 1; this.$nextTick(() => this.$refs.pin.focus()); },
            async submit() {
                if (this.busy || this.pin.length < 4) return;
                this.busy = true; this.error = '';
                const url = this.mode === 'redeem' ? @json(route('card.redeem', $card->code)) : @json(route('card.stamp', $card->code));
                try {
                    const r = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ pin: this.pin, count: this.count, lang: @json($lang) }),
                    });
                    const j = await r.json().catch(() => ({}));
                    if (r.ok) {
                        this.fresh = [this.stamps, j.stamps];
                        this.stamps = j.stamps;
                        this.message = j.redeemed ? @js($t['st_redeemed']) : '';
                        this.mode = null;
                        if (navigator.vibrate) navigator.vibrate(40);
                    } else { this.error = j.message || @js($t['error']); this.pin = ''; }
                } catch (e) { this.error = @js($t['error']); }
                this.busy = false;
            },
        };
    }
</script>
@livewireScripts
</body>
</html>
