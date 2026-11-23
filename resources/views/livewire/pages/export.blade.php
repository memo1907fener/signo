@php
    [$bg, $fg] = $page->colors();
    $accent = $page->accentColor();
    $slug = \Illuminate\Support\Str::slug($page->title) ?: 'seite';
    $formats = [
        ['id' => 'post', 'w' => 1080, 'h' => 1350, 'label' => __('Instagram-Beitrag'), 'hint' => '1080 × 1350'],
        ['id' => 'story', 'w' => 1080, 'h' => 1920, 'label' => __('Story & Status'), 'hint' => '1080 × 1920'],
        ['id' => 'square', 'w' => 1080, 'h' => 1080, 'label' => __('Quadrat'), 'hint' => '1080 × 1080'],
        ['id' => 'a4', 'w' => 1240, 'h' => 1754, 'label' => __('A4 zum Drucken'), 'hint' => __('Aushang, Schaufenster'), 'print' => true, 'scale' => 2],
    ];
@endphp
<div x-data="{ busy: null, error: '' }">
    <a href="{{ route('pages.edit', $page) }}" wire:navigate class="text-sm text-muted hover:text-ink">← {{ $page->title }}</a>
    <h1 class="mt-3 text-3xl font-bold tracking-tight">{{ __('Export für Druck & Social Media') }}</h1>
    <p class="mt-1 text-muted">{{ __('Dieselbe Seite als Bild für Instagram, als Aushang und als Tischaufsteller mit QR-Code.') }}</p>
    <p class="error" x-show="error" x-text="error"></p>

    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($formats as $f)
            @php $k = 250 / $f['w']; @endphp
            <div class="card p-4">
                <div class="mx-auto overflow-hidden rounded-xl ring-1 ring-black/10" style="width: 250px; height: {{ round($f['h'] * $k) }}px">
                    <div style="width: {{ $f['w'] }}px; height: {{ $f['h'] }}px; transform: scale({{ $k }}); transform-origin: 0 0;">
                        <div id="ex-{{ $f['id'] }}" class="sg-stage" style="width: {{ $f['w'] }}px; height: {{ $f['h'] }}px">
                            @include('display.page', ['page' => $still, 'mode' => 'tv'])
                        </div>
                    </div>
                </div>
                <p class="mt-4 font-semibold">{{ $f['label'] }}</p>
                <p class="text-xs text-muted">{{ $f['hint'] }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" class="btn-primary btn-sm" :disabled="busy"
                            @click="busy = '{{ $f['id'] }}'; error = ''; signoExport('ex-{{ $f['id'] }}', 'signo-{{ $slug }}-{{ $f['id'] }}', {{ $f['scale'] ?? 1 }}).catch(() => error = @js(__('Das Bild konnte nicht erstellt werden. Bitte in Chrome, Edge oder Safari versuchen.'))).finally(() => busy = null)">
                        <span x-show="busy !== '{{ $f['id'] }}'">{{ __('Herunterladen') }}</span><span x-show="busy === '{{ $f['id'] }}'" x-cloak>…</span>
                    </button>
                    @if ($f['print'] ?? false)
                        <button type="button" class="btn-ghost btn-sm" :disabled="busy"
                                @click="busy = '{{ $f['id'] }}p'; error = ''; signoPrint('ex-{{ $f['id'] }}', 'A4 portrait', 2).catch(() => error = @js(__('Drucken hat nicht geklappt.'))).finally(() => busy = null)">{{ __('Drucken / PDF') }}</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tischaufsteller --}}
    <h2 class="mt-12 text-xl font-bold">{{ __('Tischaufsteller mit QR-Code') }}</h2>
    @if (! $page->is_public)
        <p class="mt-2 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">{{ __('Diese Seite ist nicht per QR-Code abrufbar. Schalten Sie das im Editor ein, damit der Aufsteller funktioniert.') }}</p>
    @else
        <div class="mt-4 grid gap-8 md:grid-cols-[auto_1fr]">
            @php $tw = 1240; $th = 1748; $k = 300 / $tw; @endphp
            <div class="overflow-hidden rounded-xl shadow-lg ring-1 ring-black/10" style="width: 300px; height: {{ round($th * $k) }}px">
                <div style="width: {{ $tw }}px; height: {{ $th }}px; transform: scale({{ $k }}); transform-origin: 0 0;">
                    <div id="ex-tent" style="width: {{ $tw }}px; height: {{ $th }}px; background: {{ $bg }}; color: {{ $fg }}; font-family: 'Inter Variable', system-ui, sans-serif; display: flex; flex-direction: column; align-items: center; text-align: center; padding: 120px 100px; box-sizing: border-box;">
                        @if ($org->showsLogo())
                            <img src="{{ $org->logoUrl() }}" alt="" style="max-height: 200px; max-width: 640px; object-fit: contain;">
                        @else
                            <div style="font-size: 44px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; opacity: .7;">{{ $org->name }}</div>
                        @endif
                        <div style="margin-top: 90px; font-size: 104px; line-height: 1.04; font-weight: 800; letter-spacing: -.03em;" class="{{ in_array($page->font, ['elegant', 'bistro']) ? 'font-serif' : '' }}" x-text="$wire.tentLine">{{ $tentLine }}</div>
                        <div style="width: 150px; height: 12px; border-radius: 99px; background: {{ $accent }}; margin-top: 54px;"></div>
                        <div style="margin-top: auto; background: #fff; padding: 46px; border-radius: 56px; box-shadow: 0 30px 80px rgba(0,0,0,.18);">
                            <canvas data-qr="{{ $page->publicUrl() }}" data-size="560"></canvas>
                        </div>
                        <div style="margin-top: 70px; font-size: 46px; font-weight: 600;">{{ __('Kamera öffnen und QR-Code scannen') }}</div>
                        <div style="margin-top: 16px; font-size: 34px; opacity: .6;">{{ __('Immer aktuell · ohne App') }}</div>
                    </div>
                </div>
            </div>
            <div class="max-w-md">
                <label class="label" for="tl">{{ __('Überschrift') }}</label>
                <input id="tl" wire:model="tentLine" maxlength="40" class="input">
                <p class="hint">{{ __('Format A6 hoch (10,5 × 14,8 cm) – passt in handelsübliche Acryl-Aufsteller. Farben und Logo kommen von dieser Seite.') }}</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <button type="button" class="btn-primary" :disabled="busy" @click="busy = 'tent'; error = ''; signoExport('ex-tent', 'signo-{{ $slug }}-aufsteller', 1).catch(() => error = @js(__('Das Bild konnte nicht erstellt werden. Bitte in Chrome, Edge oder Safari versuchen.'))).finally(() => busy = null)">{{ __('Herunterladen') }}</button>
                    <button type="button" class="btn-ghost" :disabled="busy" @click="busy = 'tentp'; error = ''; signoPrint('ex-tent', 'A6 portrait', 1).catch(() => error = @js(__('Drucken hat nicht geklappt.'))).finally(() => busy = null)">{{ __('Drucken / PDF') }}</button>
                </div>
                <p class="hint mt-4">{{ __('Tipp: Im Druckdialog „Als PDF speichern“ wählen, um die Datei an eine Druckerei zu schicken.') }}</p>
            </div>
        </div>
    @endif
</div>
