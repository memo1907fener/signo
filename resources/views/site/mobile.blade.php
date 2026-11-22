@php
    $dark = $page->theme === 'dark';
    $t = \App\Support\Ui::mobile($lang);
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $page->title }} · {{ $org->name }}</title>
    <meta name="theme-color" content="{{ $dark ? '#111214' : '#f7f4ef' }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/player.css'])
    <style>
        html, body { background: {{ $dark ? '#111214' : '#f7f4ef' }}; overflow: auto; cursor: auto; height: auto; }
        body { font-family: "Inter Variable", system-ui, sans-serif; color: {{ $dark ? '#f5f2ec' : '#17140f' }}; }
        .sg-bar { position: sticky; top: 0; z-index: 5; backdrop-filter: blur(10px); background: {{ $dark ? 'rgba(17,18,20,.88)' : 'rgba(247,244,239,.88)' }}; }
        .sg-nav { display: flex; gap: 8px; overflow-x: auto; padding: 12px 16px 6px; }
        .sg-nav a, .sg-langs a { white-space: nowrap; font-size: 14px; font-weight: 600; padding: 7px 14px; border-radius: 99px; text-decoration: none;
            color: inherit; border: 1px solid {{ $dark ? 'rgba(255,255,255,.18)' : 'rgba(0,0,0,.12)' }}; }
        .sg-nav a.on { background: {{ $page->accentColor() }}; border-color: transparent; color: #fff; }
        .sg-langs { display: flex; gap: 6px; overflow-x: auto; padding: 6px 16px 10px; }
        .sg-langs a { font-size: 12px; padding: 4px 10px; }
        .sg-langs a.on { background: {{ $dark ? '#f5f2ec' : '#17140f' }}; color: {{ $dark ? '#17140f' : '#fff' }}; border-color: transparent; }
        .sg-org { text-align: center; padding: 18px 16px 0; font: 600 13px "Inter Variable", system-ui, sans-serif; letter-spacing: .06em; text-transform: uppercase; color: {{ $dark ? '#a39d93' : '#6e675d' }}; }
        .sg-made { text-align: center; padding: 0 0 110px; font-size: 12px; }
        .sg-made a { color: {{ $dark ? '#6f6a63' : '#9a9388' }}; text-decoration: none; }
        /* Warenkorb */
        .cart-btn { position: fixed; left: 16px; right: 16px; bottom: max(16px, env(safe-area-inset-bottom)); z-index: 20; display: flex; justify-content: space-between; align-items: center;
            padding: 16px 20px; border: 0; border-radius: 18px; background: {{ $page->accentColor() }}; color: #fff; font: 700 16px "Inter Variable", system-ui; box-shadow: 0 10px 30px rgba(0,0,0,.25); cursor: pointer; max-width: 728px; margin: 0 auto; }
        .sheet-bg { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 30; }
        .sheet { position: fixed; left: 0; right: 0; bottom: 0; z-index: 31; max-height: 88vh; overflow: auto; border-radius: 22px 22px 0 0; padding: 22px 20px calc(24px + env(safe-area-inset-bottom));
            background: {{ $dark ? '#1b1c1f' : '#fff' }}; max-width: 760px; margin: 0 auto; }
        .sheet h3 { font-size: 20px; font-weight: 750; margin: 0 0 12px; }
        .row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid {{ $dark ? 'rgba(255,255,255,.1)' : 'rgba(0,0,0,.08)' }}; }
        .row .nm { flex: 1; font-weight: 600; }
        .qty { display: flex; align-items: center; gap: 8px; }
        .qty button { width: 30px; height: 30px; border-radius: 99px; border: 1px solid {{ $dark ? 'rgba(255,255,255,.25)' : 'rgba(0,0,0,.18)' }}; background: transparent; color: inherit; font-size: 18px; cursor: pointer; }
        .field { width: 100%; box-sizing: border-box; margin-top: 10px; padding: 12px 14px; border-radius: 12px; font: 16px "Inter Variable", system-ui;
            border: 1px solid {{ $dark ? 'rgba(255,255,255,.2)' : 'rgba(0,0,0,.15)' }}; background: transparent; color: inherit; }
        .go { width: 100%; margin-top: 16px; padding: 15px; border: 0; border-radius: 14px; background: {{ $page->accentColor() }}; color: #fff; font: 700 16px "Inter Variable", system-ui; cursor: pointer; }
        .go:disabled { opacity: .5; }
        .err { color: #e5484d; font-size: 14px; margin-top: 10px; }
        .muted { opacity: .6; font-size: 13px; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="signoCart()">
    @if ($others->count() > 1 || count($available))
        <div class="sg-bar">
            @if ($others->count() > 1)
                <nav class="sg-nav">
                    @foreach ($others as $o)
                        <a href="{{ url('/m/' . $o->slug) }}{{ $lang !== 'de' ? '?lang=' . $lang : '' }}" class="{{ $o->id === $page->id ? 'on' : '' }}">{{ $o->title }}</a>
                    @endforeach
                </nav>
            @endif
            @if (count($available))
                <div class="sg-langs">
                    <a href="?lang=de" class="{{ $lang === 'de' ? 'on' : '' }}">Deutsch</a>
                    @foreach ($available as $l)
                        <a href="?lang={{ $l }}" class="{{ $lang === $l ? 'on' : '' }}">{{ \App\Support\Translations::LANGUAGES[$l][0] }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="sg-org">{{ $org->name }}</div>
    @include('display.page', ['page' => $page, 'mode' => 'mobile', 'ordering' => $ordering])
    <div class="sg-made"><a href="{{ url('/') }}">Erstellt mit Signo</a></div>

    @if ($ordering)
        <button class="cart-btn" x-show="count() > 0" x-cloak @click="open = true">
            <span>{{ $t['cart'] }} (<span x-text="count()"></span>)</span>
            <span x-text="euro(total())"></span>
        </button>

        <div class="sheet-bg" x-show="open" x-cloak @click="open = false"></div>
        <div class="sheet" x-show="open" x-cloak>
            <h3>{{ $t['your_order'] }}</h3>
            <template x-for="(it, k) in items" :key="k">
                <div class="row">
                    <span class="nm" x-text="it.name"></span>
                    <span class="qty">
                        <button type="button" @click="dec(k)">−</button>
                        <span x-text="it.q"></span>
                        <button type="button" @click="it.q < 20 && it.q++">+</button>
                    </span>
                    <span style="min-width:72px;text-align:right" x-text="euro(it.p * it.q)"></span>
                </div>
            </template>
            <div class="row" style="border:0;font-weight:750"><span class="nm">{{ $t['total'] }}</span><span x-text="euro(total())"></span></div>

            @if ($org->ordering_mode === 'table')
                <input class="field" x-model="table" placeholder="{{ $t['table'] }}" inputmode="numeric">
            @else
                <input class="field" x-model="name" placeholder="{{ $t['name'] }}" autocomplete="given-name">
            @endif
            <input class="field" x-model="note" placeholder="{{ $t['note'] }}" maxlength="300">
            <input type="text" x-model="website" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off" aria-hidden="true">
            @if ($org->ordering_note)<p class="muted" style="margin-top:10px">{{ $org->ordering_note }}</p>@endif
            <p class="err" x-show="error" x-text="error"></p>
            <button class="go" :disabled="busy" @click="send()" x-text="busy ? '…' : @js($t['send'])"></button>
            <button type="button" class="go" style="background:transparent;color:inherit;margin-top:6px" @click="open = false">{{ $t['back'] }}</button>
        </div>

        <script>
            function signoCart() {
                return {
                    items: [], open: false, busy: false, error: '',
                    name: '', note: '', website: '',
                    table: new URLSearchParams(location.search).get('tisch') || '',
                    add(s, i, name, p) {
                        const k = this.items.findIndex((x) => x.s === s && x.i === i);
                        if (k >= 0) { if (this.items[k].q < 20) this.items[k].q++; } else this.items.push({ s, i, name, p, q: 1 });
                        if (navigator.vibrate) navigator.vibrate(20);
                    },
                    dec(k) { if (--this.items[k].q <= 0) this.items.splice(k, 1); if (!this.items.length) this.open = false; },
                    count() { return this.items.reduce((a, x) => a + x.q, 0); },
                    total() { return this.items.reduce((a, x) => a + x.p * x.q, 0); },
                    euro(c) { return (c / 100).toLocaleString('de-AT', { style: 'currency', currency: 'EUR' }); },
                    async send() {
                        this.error = ''; this.busy = true;
                        try {
                            const r = await fetch(@json(route('order.store', $page->slug)), {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                body: JSON.stringify({
                                    items: this.items.map((x) => ({ s: x.s, i: x.i, q: x.q })),
                                    name: this.name, table: this.table, note: this.note, website: this.website, lang: @json($lang),
                                }),
                            });
                            const j = await r.json().catch(() => ({}));
                            if (r.ok && j.url) { location.href = j.url; return; }
                            this.error = j.message || @js($t['error']);
                        } catch (e) { this.error = @js($t['error']); }
                        this.busy = false;
                    },
                };
            }
        </script>
    @else
        <script>function signoCart() { return {}; }</script>
    @endif
    @livewireScripts
</body>
</html>
