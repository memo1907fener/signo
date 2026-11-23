@php
    $plans = collect(config('signo.plans'))->map(fn ($p) => array_merge($p, ['name' => __($p['name']), 'features' => array_map('__', $p['features'])]))->all();
    $tvHost = str_replace(['https://', 'http://'], '', url('/tv'));
    $logo = '<svg viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="9" fill="#ff5a36"/><rect x="7" y="9" width="18" height="12" rx="2.5" fill="none" stroke="#fff" stroke-width="2.4"/><circle cx="16" cy="25" r="2" fill="#fff"/></svg>';
    // Manifest: Wörter in *Sternchen* werden hervorgehoben
    $manifest = array_map(fn ($w) => [trim($w, '*'), str_starts_with($w, '*')], explode(' ', __('Kein Grafikprogramm. Kein Techniker. Kein Drucker. Nur *Ihr* *Foto* – und *zwei* *Minuten.*')));
    $more = [
        ['Allergene mit Filter', 'Die 14 Allergene und vegan, vegetarisch, scharf oder halal je Gericht. Gäste blenden am Handy aus, was sie nicht vertragen.'],
        ['Heute aus', 'Ein Tipp, und das Gericht ist am Bildschirm als ausverkauft markiert. Am nächsten Tag ist es von selbst wieder da.'],
        ['Wochenmenü', 'Mittagsmenü für die ganze Woche eintragen – der Bildschirm zeigt jeden Tag das richtige.'],
        ['Happy Hour', 'Aktionspreise gelten zu Ihren Zeiten und springen danach von selbst zurück.'],
        ['Eilmeldung', '„Heute geschlossen“ steht in 30 Sekunden auf allen Bildschirmen und Handy-Seiten.'],
        ['Feedback der Gäste', 'Sterne und ein Satz per QR-Code. Kritik landet bei Ihnen, der Link zu Google ist gleich dabei.'],
        ['Stempelkarte am Handy', 'Die Treuekarte ohne App und ohne Papier. Gestempelt wird mit dem Code Ihres Personals.'],
        ['Ihr Logo, Ihre Farbe', 'Logo hochladen – Signo liest die Farbe heraus und zeigt das Logo auf jeder Seite.'],
        ['Druck und Social Media', 'Jede Seite als Instagram-Bild, als Aushang in A4 und als Tischaufsteller mit QR-Code.'],
        ['Sechs Designs', 'Modern, elegant, Bistro, Kreidetafel, minimal oder Neon – jeweils hell und dunkel.'],
        ['Statistik', 'Wie oft der QR-Code gescannt wird, was am meisten bestellt wird und wann.'],
        ['Ausfall-Alarm', 'Fällt ein Bildschirm während der Öffnungszeit aus, bekommen Sie eine E-Mail.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Signo – Ihre Speisekarte leuchtet ab heute') }}</title>
    <meta name="description" content="{{ __('Foto hochladen, fertig: Signo macht aus Ihrer Speisekarte, Preisliste oder Aktion die Anzeige für den Fernseher im Geschäft – mit QR-Code fürs Handy, in 16 Sprachen und mit Bestellung per Handy. 14 Tage kostenlos.') }}">
    <script>window.SIGNO_T = @js(['photo' => __('Das Foto konnte nicht gelesen werden.'), 'offline' => __('Keine Verbindung.'), 'customName' => __('Viele Bildschirme'), 'customFeatures' => [__('Mehrere Standorte in einem Konto'), __('Eigene Konditionen'), __('Persönliche Einrichtung')]]);</script>
    <meta name="theme-color" content="#0a0b1e">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/site.css', 'resources/js/site.js'])
    @livewireStyles
</head>
<body class="site">

<div class="sky" aria-hidden="true"><i class="sky-dawn"></i><i class="sky-sun"></i><i class="grain"></i></div>

<header class="nav">
    <div class="nav-in">
        <a href="/" class="logo" aria-label="{{ __('Signo Startseite') }}">{!! $logo !!}signo</a>
        <nav class="nav-links" aria-label="{{ __('Hauptnavigation') }}">
            <a href="#geschichte">{{ __('So geht\'s') }}</a>
            <a href="#ausprobieren">{{ __('Ausprobieren') }}</a>
            <a href="#funktionen">{{ __('Funktionen') }}</a>
            <a href="#preise">{{ __('Preise') }}</a>
            <a href="#fragen">{{ __('Fragen') }}</a>
        </nav>
        @auth
            <a href="{{ route('pages.index') }}" class="btn btn-ember" style="margin-left:auto">{{ __('Zu meinen Inhalten') }}</a>
        @else
            <a href="{{ route('login') }}" class="login">{{ __('Anmelden') }}</a>
            <a href="{{ route('register') }}" class="btn btn-ember">{{ __('Kostenlos testen') }}</a>
        @endauth
    </div>
</header>

<main>
{{-- ================= Hero ================= --}}
<section class="hero">
    <i class="bokeh b1"></i><i class="bokeh b2"></i><i class="bokeh b3"></i><i class="bokeh b4"></i>
    <div class="wrap hero-copy">
        <p class="badge rv"><i class="pulse"></i>{{ __(':n Tage gratis. Ohne Kreditkarte.', ['n' => config('signo.trial_days')]) }}</p>
        <h1 class="h-xl">
            <span class="line"><span class="line-in">{{ __('Ihre') }} <span class="slot" data-slot="2600"><span class="on">{{ __('Speisekarte') }}</span><span>{{ __('Preisliste') }}</span><span>{{ __('Mittagskarte') }}</span><span>{{ __('Tagesaktion') }}</span></span></span></span>
            <span class="line"><span class="line-in" style="--d:1">{{ __('leuchtet ab heute.') }}</span></span>
        </h1>
        <p class="sub rv" style="--d:5">{{ __('Foto hochladen – Signo macht daraus die Anzeige für Ihren Fernseher, die Handy-Seite mit QR-Code und auf Wunsch gleich die Bestellung dazu.') }}</p>
        <div class="ctas rv" style="--d:7">
            <a href="{{ route('register') }}" class="btn btn-ember">{{ __(':n Tage kostenlos testen', ['n' => config('signo.trial_days')]) }}</a>
            <a href="#ausprobieren" class="btn btn-glass">{{ __('Ohne Anmeldung ausprobieren') }}
                <svg class="arr" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v11M3.5 8.5 8 13l4.5-4.5"/></svg></a>
        </div>
    </div>

    <div class="hero-stage" x-data="signoHero({{ count($heroPages) }})" aria-label="{{ __('Ein Fernseher zeigt eine Speisekarte') }}">
        <div class="hero-glow"></div>
        <div class="screen3d">
            <div class="tv"><div class="tv-screen">
                @foreach ($heroPages as $k => $p)
                    <div class="sg-stage" :class="{ on: i === {{ $k }} }" @class(['on first' => $k === 0])>@include('display.page', ['page' => $p, 'mode' => 'tv'])</div>
                @endforeach
                <div class="scan"></div>
            </div></div>
        </div>
        <div class="chip c1" aria-hidden="true"><span>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffb454" stroke-width="2" stroke-linecap="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>
            {{ __('Schnitzel') }} <s>18,90</s> <b>19,90</b></span></div>
        <div class="chip c2" aria-hidden="true"><span>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffb454" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.2 3 14.800 0 18M12 3c-3 3.200-3 14.800 0 18"/></svg>
            {{ __('Menu · Menù · Carte') }}</span></div>
        <div class="chip c3" aria-hidden="true"><span>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffb454" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><path d="M14 14h3v3h-3zM18.500 18.500H21V21h-2.500z" fill="#ffb454" stroke="none"/></svg>
            {{ __('QR-Code fürs Handy') }}</span></div>
        <div class="chip c4" aria-hidden="true"><span>
            <svg viewBox="0 0 24 24" fill="none" stroke="#5fe39a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12.500 2.800 2.800L16 9.800"/></svg>
            {{ __('Bestellung') }} <b class="ok">#27</b> {{ __('fertig') }}</span></div>
    </div>
</section>

{{-- ================= Laufband ================= --}}
<div class="band" aria-hidden="true">
    <div class="band-row">
        @for ($r = 0; $r < 2; $r++)
            <span>{{ __('Speisekarten') }}</span><i>✳</i><em>{{ __('Preislisten') }}</em><i>✳</i><span>{{ __('Wochenmenüs') }}</span><i>✳</i><em>{{ __('Öffnungszeiten') }}</em><i>✳</i><span>{{ __('Happy Hour') }}</span><i>✳</i><em>{{ __('Abholnummern') }}</em><i>✳</i><span>{{ __('Wartezimmer-Infos') }}</span><i>✳</i>
        @endfor
    </div>
</div>

{{-- ================= Signatur: Scroll-Geschichte ================= --}}
<section class="story" id="geschichte" aria-label="{{ __('Vom Foto zum Bildschirm in vier Schritten') }}">
    <div class="story-stage">
        <div class="story-text">
            <div class="chap" style="--o:1;--y:0px">
                <div class="num">1</div>
                <h3>{{ __('Fotografieren.') }}</h3>
                <p>{{ __('Speisekarte, Preisliste oder Plakat – ein Foto oder PDF genügt. Sie können auch einfach tippen.') }}</p>
            </div>
            <div class="chap">
                <div class="num">2</div>
                <h3>{{ __('Signo gestaltet.') }}</h3>
                <p>{{ __('Die KI überträgt jeden Eintrag und jeden Preis. Hell oder dunkel, Ihre Farbe – die Schriftgröße passt sich von selbst an.') }}</p>
            </div>
            <div class="chap">
                <div class="num">3</div>
                <h3>{{ __('Auch am Handy.') }}</h3>
                <p>{{ __('Jede Seite bekommt einen QR-Code. Gäste scannen ihn und sehen dieselbe Karte, immer aktuell.') }}</p>
            </div>
            <div class="chap">
                <div class="num">4</div>
                <h3>{{ __('In 16 Sprachen. Mit Bestellung.') }}</h3>
                <p>{{ __('Gäste lesen in ihrer Sprache und bestellen per Handy. Die Abholnummer erscheint am Bildschirm.') }}</p>
            </div>
            <div class="rail" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
        </div>

        <div class="story-devices" aria-hidden="true">
            <div class="s-glow"></div>

            <div class="s-tv">
                <div class="s-langs"><b class="on">{{ __('Deutsch') }}</b><b>{{ __('English') }}</b><b>{{ __('Italiano') }}</b></div>
                <div class="tv"><div class="tv-screen">
                    <div class="sg-stage lang-de">@include('display.page', ['page' => $story['de'], 'mode' => 'tv'])</div>
                    <div class="sg-stage lang-en">@include('display.page', ['page' => $story['en'], 'mode' => 'tv'])</div>
                    <div class="sg-stage lang-it">@include('display.page', ['page' => $story['it'], 'mode' => 'tv'])</div>
                    <div class="s-printline"></div>
                </div></div>
            </div>

            <div class="s-frame"><i></i><i></i><i></i><i></i></div>
            <div class="s-paper">
                <b>Speisekarte</b>
                <u>{{ __('Vorspeisen') }}</u>
                <i><span>{{ __('Frittatensuppe') }}</span><span>4,90</span></i>
                <i><span>{{ __('Gemischter Salat') }}</span><span>5,50</span></i>
                <u>{{ __('Hauptspeisen') }}</u>
                <i><span>{{ __('Wiener Schnitzel') }}</span><span>18,90</span></i>
                <i><span>{{ __('Käsespätzle') }}</span><span>12,50</span></i>
                <i><span>{{ __('Gemüsecurry') }}</span><span>11,90</span></i>
                <u>{{ __('Nachspeisen') }}</u>
                <i><span>{{ __('Kaiserschmarrn') }}</span><span>8,90</span></i>
                <div class="s-beam"></div>
            </div>

            <div class="s-qr"><canvas data-qr="{{ route('register') }}"></canvas></div>
            <div class="s-phone">
                <div class="phone">
                    <div class="phone-notch"></div>
                    <div class="phone-screen">
                        <div class="phone-scale">@include('display.page', ['page' => $story['de'], 'mode' => 'mobile'])</div>
                        <div class="phone-scale p-en" style="opacity:0">@include('display.page', ['page' => $story['en'], 'mode' => 'mobile'])</div>
                    </div>
                </div>
            </div>
            <div class="s-ticket">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.500 4.500 4.500L19 7.500"/></svg>
                <span>#27<small>{{ __('Bitte abholen') }}</small></span>
            </div>
        </div>
    </div>
</section>

{{-- ================= Manifest & Zahlen ================= --}}
<section class="manifest">
    <div class="wrap">
        <p>@foreach ($manifest as [$w, $hl])<span class="w {{ $hl ? 'it' : '' }}">{{ $w }}</span> @endforeach</p>
        <div class="stats">
            <div class="stat rv"><b data-count="2">0<small>{{ __('Min.') }}</small></b><span>{{ __('vom Foto bis zur fertigen Anzeige') }}</span></div>
            <div class="stat rv" style="--d:1"><b data-count="16">0<small>{{ __('Sprachen') }}</small></b><span>{{ __('automatisch übersetzt, Preise bleiben gleich') }}</span></div>
            <div class="stat rv" style="--d:2"><b data-count="0">0<small>{{ __('€ Gerät') }}</small></b><span>{{ __('läuft auf jedem Fernseher mit Browser') }}</span></div>
            <div class="stat rv" style="--d:3"><b data-count="{{ config('signo.trial_days') }}">0<small>{{ __('Tage gratis') }}</small></b><span>{{ __('ohne Kreditkarte, endet von selbst') }}</span></div>
        </div>
    </div>
</section>

{{-- ================= Live-Demo ================= --}}
<section class="sec" id="ausprobieren">
    <div class="wrap">
        <div class="sec-head mkw">
            <p class="kick rv">{{ __('Ohne Anmeldung') }}</p>
            <h2 class="h-l rv" style="--d:1">{{ __('Probieren Sie es') }} <span class="mk">{{ __('gleich hier.') }}</span></h2>
            <p class="lead rv" style="--d:2">{{ __('Ein Eintrag pro Zeile, der Preis ans Ende. Rechts sehen Sie sofort, wie es am Fernseher und am Handy aussieht.') }}</p>
        </div>
        <div class="glass demo-shell rv" x-data="signoDemo(@js($demoText), @js("\nKaiserschmarrn – mit Zwetschkenröster 8,90"))">
            <div class="demo-editor">
                <div class="editor">
                    <div class="editor-bar"><i></i><i></i><i></i><span>{{ __('Ihre Karte') }}</span><span class="live"><i class="pulse"></i>{{ __('live') }}</span></div>
                    <label for="demo-text" class="sr">{{ __('Ihre Karte') }}</label>
                    <textarea id="demo-text" x-ref="ta" x-model="text" @focus="touched = true" spellcheck="false"></textarea>
                </div>
                <div class="demo-tools">
                    <div class="seg" role="group" aria-label="{{ __('Hell oder dunkel') }}">
                        <button type="button" @click="theme = 'light'" :aria-pressed="theme === 'light'">{{ __('Hell') }}</button>
                        <button type="button" @click="theme = 'dark'" :aria-pressed="theme === 'dark'">{{ __('Dunkel') }}</button>
                    </div>
                    <div class="seg seg-wrap" role="group" aria-label="{{ __('Design') }}">
                        @foreach (config('signo.fonts') as $fk => $fl)
                            <button type="button" @click="font = '{{ $fk }}'" :aria-pressed="font === '{{ $fk }}'">{{ __($fl) }}</button>
                        @endforeach
                    </div>
                    <div class="dots" role="group" aria-label="{{ __('Farbe') }}">
                        @foreach (config('signo.accents') as $key => $hex)
                            <button type="button" @click="accent = '{{ $key }}'" :aria-pressed="accent === '{{ $key }}'" style="background: {{ $hex }}" aria-label="{{ __('Farbe') }} {{ $key }}"></button>
                        @endforeach
                    </div>
                </div>
                @if ($aiDemo)
                    <p style="margin:18px 0 0">
                        <label class="btn btn-glass" :style="busy && 'opacity:.6'">
                            <input type="file" accept="image/*,application/pdf" class="sr" @change="photo($event)" :disabled="busy">
                            <span x-text="busy ? @js(__('Die KI liest Ihre Karte …')) : @js(__('Oder Foto Ihrer Karte hochladen'))"></span>
                        </label>
                    </p>
                @endif
                <p class="err" x-show="error" x-text="error" x-cloak></p>
                <p class="demo-note">{{ __('Gefällt es Ihnen?') }} <a href="{{ route('register') }}">{{ __('Konto anlegen') }}</a> – {{ __('in zwei Minuten läuft es auf Ihrem Fernseher.') }}</p>
            </div>
            <div class="demo-out">
                <div class="tv"><div class="tv-screen"><div class="sg-stage on" x-ref="tv"></div></div></div>
                <div class="phone demo-phone">
                    <div class="phone-notch"></div>
                    <div class="phone-screen"><div class="phone-scale" x-ref="phone"></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================= Branchen ================= --}}
<section class="sec" id="branchen" x-data="signoIndustries('gastro')">
    <div class="wrap">
        <div class="sec-head mkw">
            <p class="kick rv">{{ __('Für jeden Betrieb') }}</p>
            <h2 class="h-l rv" style="--d:1">{{ __('Fertige Seiten für') }} <span class="mk">{{ __('Ihre Branche.') }}</span></h2>
            <p class="lead rv" style="--d:2">{{ __('Nach der Anmeldung warten diese Beispiele schon auf Sie. Sie ändern nur Texte und Preise.') }}</p>
        </div>
        <div class="tabs rv" role="tablist">
            @foreach ($industries as $key => $ind)
                <button type="button" role="tab" @click="pick('{{ $key }}')" :aria-selected="ind === '{{ $key }}'">{{ __($ind['label']) }}</button>
            @endforeach
        </div>
        @foreach ($industries as $key => $ind)
            <div class="ind-stage" x-show="ind === '{{ $key }}'" @if ($key !== 'gastro') x-cloak @endif role="tabpanel">
                <div>
                    <div class="tv"><div class="tv-screen">
                        @foreach ($ind['pages'] as $n => $p)
                            <div class="sg-stage" :class="{ on: page === {{ $n }} }" @class(['on' => $n === 0])>@include('display.page', ['page' => $p, 'mode' => 'tv'])</div>
                        @endforeach
                    </div></div>
                    <div class="ind-pages">
                        @foreach ($ind['pages'] as $n => $p)
                            <button type="button" class="mini" @click="go({{ $n }})" :aria-current="page === {{ $n }}" aria-label="{{ $p->title }}">
                                <span class="sg-stage">@include('display.page', ['page' => $p, 'mode' => 'tv'])</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="phone-col">
                    <div class="phone">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            @foreach ($ind['pages'] as $n => $p)
                                <div class="phone-scale" x-show="page === {{ $n }}" @if ($n) x-cloak @endif>@include('display.page', ['page' => $p, 'mode' => 'mobile'])</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= Funktionen ================= --}}
<section class="sec" id="funktionen">
    <div class="wrap">
        <div class="sec-head mkw">
            <p class="kick rv">{{ __('In jedem Paket enthalten') }}</p>
            <h2 class="h-l rv" style="--d:1">{{ __('Mehr als eine') }} <span class="mk">{{ __('Speisekarte.') }}</span></h2>
        </div>
        <div class="bento">
            <article class="tile w7 rv">
                <div class="viz v-voice" aria-hidden="true">
                    <div class="v-wave">@for ($i = 0; $i < 11; $i++)<i></i>@endfor</div>
                    <div class="v-quote">{{ __('„Schnitzel kostet') }}<br>{{ __('jetzt') }} <b>19,90</b>.“</div>
                    <div class="v-price"><s>18,90</s>19,90</div>
                </div>
                <h3 class="h-m">{{ __('Ändern per Sprache') }}</h3>
                <p>{{ __('Ins Handy sagen, die Vorschau prüfen, übernehmen. Kein Formular, kein Suchen.') }}</p>
            </article>
            <article class="tile w5 rv" style="--d:1">
                <div class="viz v-lang" aria-hidden="true"><span class="slot" data-slot="1700"><span class="on">Speisekarte</span><span>{{ __('Menu') }}</span><span>{{ __('Menù') }}</span><span>{{ __('Carte') }}</span><span>{{ __('Jídelní lístek') }}</span><span>{{ __('Étlap') }}</span></span></div>
                <h3 class="h-m">{{ __('16 Sprachen automatisch') }}</h3>
                <p>{{ __('Gäste sehen die Karte in ihrer Sprache. Am Bildschirm wechseln die Sprachen auf Wunsch ab.') }}</p>
            </article>
            <article class="tile rv">
                <div class="viz v-order" aria-hidden="true"><span><b>#27</b>{{ __('Neu') }}</span><span><b>#27</b>{{ __('In Arbeit') }}</span><span><b>#27</b>{{ __('Fertig') }}</span></div>
                <h3 class="h-m">{{ __('Bestellen per QR-Code') }}</h3>
                <p>{{ __('Gäste bestellen am Handy, die Küche sieht es am Tablet, die Nummer erscheint am Bildschirm.') }}</p>
            </article>
            <article class="tile rv" style="--d:1">
                <div class="viz v-clock" aria-hidden="true"><span data-clock>{{ now()->format('H:i') }}</span><small>{{ __('Uhr, Wetter,') }}<br>{{ __('Countdown,') }}<br>{{ __('Laufschrift') }}</small></div>
                <h3 class="h-m">{{ __('Live-Bausteine') }}</h3>
                <p>{{ __('Für Empfang und Wartebereich – immer aktuell, ohne dass jemand etwas tun muss.') }}</p>
            </article>
            <article class="tile rv" style="--d:2">
                <div class="viz v-week" aria-hidden="true"><i class="on" style="--h:64px">{{ __('Mo') }}</i><i class="on" style="--h:80px">{{ __('Di') }}</i><i class="on" style="--h:58px">{{ __('Mi') }}</i><i class="on" style="--h:88px">{{ __('Do') }}</i><i class="on" style="--h:72px">{{ __('Fr') }}</i><i style="--h:30px">{{ __('Sa') }}</i><i style="--h:30px">{{ __('So') }}</i></div>
                <h3 class="h-m">{{ __('Zeitpläne') }}</h3>
                <p>{{ __('Frühstückskarte bis 11 Uhr, Mittagsmenü nur Mo–Fr, die Aktion endet von selbst.') }}</p>
            </article>
            <article class="tile w5 rv">
                <div class="viz v-off" aria-hidden="true">
                    <svg viewBox="0 0 48 48" fill="none" stroke="#ff9c8a" stroke-width="3" stroke-linecap="round"><path d="M6 18c10-9 26-9 36 0M13 26c6-5.500 16-5.500 22 0M20 33.500c2.500-2 5.500-2 8 0"/><path d="M8 8l32 32" stroke="#ff5a36"/></svg>
                    <div class="bar"></div><span>{{ __('läuft weiter') }}</span>
                </div>
                <h3 class="h-m">{{ __('Auch ohne Internet') }}</h3>
                <p>{{ __('Fällt die Verbindung aus, zeigt der Bildschirm den letzten Stand – samt Bildern und Videos.') }}</p>
            </article>
            <article class="tile rv" style="--d:1">
                <div class="viz v-team" aria-hidden="true"><span>M</span><span>K</span><span>A</span><span>+3</span></div>
                <h3 class="h-m">{{ __('Team und Standorte') }}</h3>
                <p>{{ __('Mitarbeiter einladen, mehrere Filialen in einem Konto.') }}</p>
            </article>
            <article class="tile w3 rv" style="--d:2">
                <div class="viz v-text" aria-hidden="true"><span>{{ __('Nur heute') }}</span><span>{{ __('Frisch aus dem Ofen') }}</span><span>{{ __('Herbst im Glas') }}</span></div>
                <h3 class="h-m">{{ __('Texte und Fotos') }}</h3>
                <p>{{ __('Stichworte genügen.') }}</p>
            </article>
        </div>

        <h3 class="more-head rv">{{ __('Und für den Alltag im Betrieb') }}</h3>
        <div class="more">
            @foreach ($more as $n => [$mt, $mp])
                <div class="more-item rv" style="--d:{{ $n % 3 }}"><h4>{{ __($mt) }}</h4><p>{{ __($mp) }}</p></div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= Preise ================= --}}
<section class="sec" id="preise">
    <div class="wrap">
        <div class="sec-head mkw">
            <p class="kick rv">{{ __('Monatlich kündbar, Preise inkl. USt.') }}</p>
            <h2 class="h-l rv" style="--d:1">{{ __('Was kostet das') }} <span class="mk">{{ __('für Sie?') }}</span></h2>
        </div>
        <div class="price-grid">
            <div class="glass calc rv" x-data="signoCalc(@js($plans))">
                <label for="screens">{{ __('Wie viele Bildschirme haben Sie?') }}</label>
                <input id="screens" type="range" min="0" max="10" step="1" x-model.number="n">
                <div class="scale"><span>{{ __('nur QR & Handy') }}</span><span>1</span><span>5</span><span>10</span></div>
                <p class="big">
                    <template x-if="plan.price"><span x-text="euro(plan.price)"></span></template>
                    <template x-if="!plan.price"><span style="font-size:.6em">{{ __('Auf Anfrage') }}</span></template>
                    <small x-show="plan.price" x-text="plan.interval === 'year' ? @js(__('pro Jahr')) : @js(__('pro Monat'))"></small>
                </p>
                <p class="plan"><strong x-text="plan.name"></strong><span x-show="perScreen()" x-text="' · ' + perScreen() + ' ' + @js(__('je Bildschirm'))"></span></p>
                <ul><template x-for="f in plan.features" :key="f"><li x-text="f"></li></template></ul>
                <a :href="plan.key === 'custom' ? 'mailto:{{ config('signo.company.email') }}' : '{{ route('register') }}'" class="btn btn-ember" x-text="plan.key === 'custom' ? @js(__('Angebot anfragen')) : @js(__(':n Tage kostenlos starten', ['n' => config('signo.trial_days')]))"></a>
            </div>
            <div class="cmp-wrap rv" style="--d:1">
                <table class="cmp">
                    <thead><tr><th></th><th>Signo</th><th>{{ __('Gedruckte Karte') }}</th><th>{{ __('Klassische Signage-Software') }}</th></tr></thead>
                    <tbody>
                        <tr><th>{{ __('Preis ändern') }}</th><td>{{ __('Sekunden, auch per Sprache') }}</td><td>{{ __('Neu drucken') }}</td><td>{{ __('Layout öffnen, bearbeiten, veröffentlichen') }}</td></tr>
                        <tr><th>{{ __('Gestaltung') }}</th><td>{{ __('Fertig aus dem Foto') }}</td><td>{{ __('Grafiker oder Copyshop') }}</td><td>{{ __('Selbst gestalten') }}</td></tr>
                        <tr><th>{{ __('Handy & QR-Code') }}</th><td>{{ __('Inklusive') }}</td><td class="no">–</td><td>{{ __('meist eigenes Produkt') }}</td></tr>
                        <tr><th>{{ __('Mehrsprachig') }}</th><td>{{ __('16 Sprachen automatisch') }}</td><td>{{ __('Je Sprache drucken') }}</td><td>{{ __('Je Sprache gestalten') }}</td></tr>
                        <tr><th>{{ __('Bestellen per Handy') }}</th><td>{{ __('Inklusive') }}</td><td class="no">–</td><td class="no">–</td></tr>
                        <tr><th>{{ __('Allergene & Ausverkauft') }}</th><td>{{ __('Ein Tipp, sofort am Bildschirm') }}</td><td>{{ __('Zettel dazuschreiben') }}</td><td>{{ __('von Hand einpflegen') }}</td></tr>
                        <tr><th>{{ __('Feedback & Stempelkarte') }}</th><td>{{ __('Inklusive') }}</td><td>{{ __('Papierkarte') }}</td><td class="no">–</td></tr>
                        <tr><th>{{ __('Gerät') }}</th><td>{{ __('Jeder Fernseher mit Browser oder Android-Box') }}</td><td class="no">–</td><td>{{ __('oft eigener Player nötig') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ================= Fragen ================= --}}
<section class="sec faq" id="fragen">
    <div class="wrap" style="max-width:880px">
        <div class="sec-head"><h2 class="h-l rv">{{ __('Häufige Fragen') }}</h2></div>
        <div class="rv">
        @foreach ([
            ['Welchen Fernseher brauche ich?', __('Jeden Fernseher oder Monitor mit Internet-Browser – oder eine günstige Android-TV-Box am HDMI-Anschluss. Dort :host öffnen und den angezeigten Code in Ihrem Konto eingeben.', ['host' => $tvHost])],
            ['Kann ich Allergene kennzeichnen?', 'Ja. Zu jedem Gericht wählen Sie die 14 kennzeichnungspflichtigen Allergene und Hinweise wie vegan oder scharf. Am Bildschirm stehen die Buchstaben mit Legende, am Handy können Gäste danach filtern. Für die Richtigkeit der Angaben bleibt der Betrieb verantwortlich.'],
            ['Wie funktioniert die Stempelkarte?', 'Der Gast scannt einen QR-Code und hat seine Karte am Handy – ohne App und ohne Anmeldung. Beim Bezahlen gibt Ihr Personal einen Code ein, der Stempel ist drauf. Ist die Karte voll, wird die Belohnung genauso eingelöst.'],
            ['Was passiert nach der Testphase?', 'Nichts, solange Sie nichts tun: Die Testphase endet automatisch, es wird nichts abgebucht. Wählen Sie ein Paket, laufen Ihre Inhalte nahtlos weiter.'],
            ['Wie funktioniert das Bestellen?', 'Gäste scannen den QR-Code, legen Gerichte in den Warenkorb und schicken die Bestellung ab. Die Küche sieht sie sofort am Tablet und meldet „Fertig“ – der Gast sieht das am Handy, die Nummer erscheint auf dem Bildschirm. Bezahlt wird wie gewohnt vor Ort.'],
            ['Welche Sprachen gibt es?', 'Englisch, Italienisch, Französisch, Spanisch, Niederländisch, Tschechisch, Slowakisch, Ungarisch, Slowenisch, Kroatisch, Polnisch, Türkisch, Ukrainisch, Russisch, Chinesisch und Japanisch.'],
            ['Was ist, wenn das Internet ausfällt?', 'Der Bildschirm spielt den zuletzt geladenen Stand weiter und holt sich Änderungen, sobald die Verbindung wieder da ist.'],
            ['Stimmen die Preise nach dem KI-Import?', 'Die KI überträgt Einträge und Preise sehr genau. Trotzdem sehen Sie danach alles in einer Vorschau und können jede Zeile prüfen, bevor sie erscheint.'],
            ['Kann ich mehrere Filialen verwalten?', 'Ja. Jeder Standort hat eigene Seiten und Bildschirme, Sie wechseln mit einem Klick. Mitarbeiter laden Sie per E-Mail ein.'],
            ['Wie steht es um den Datenschutz?', 'Signo setzt keine Werbe- oder Tracking-Cookies, Schriften werden von unserem Server geladen. Ihre Daten nutzen wir nur, um Ihre Anzeigen bereitzustellen.'],
        ] as [$q, $a])
            <details><summary>{{ __($q) }}</summary><p>{{ __($a) }}</p></details>
        @endforeach
        </div>
    </div>
</section>

{{-- ================= Schluss ================= --}}
<section class="final">
    <div class="wrap">
        <h2 class="rv">{{ __('Heute Abend eingerichtet.') }}<br><span class="it">{{ __('Morgen früh') }}</span> {{ __('läuft\'s.') }}</h2>
        <a href="{{ route('register') }}" class="btn btn-milk rv" style="--d:2; font-size:1.1rem; padding:1.15em 1.9em">{{ __(':n Tage kostenlos testen', ['n' => config('signo.trial_days')]) }}</a>
        <p class="rv" style="--d:3">{{ __('Keine Kreditkarte. Kein Gerätekauf. Endet von selbst.') }}</p>
    </div>
</section>
</main>

<footer class="foot">
    <div class="wrap">
        <span>© {{ date('Y') }} {{ config('signo.company.name') }}</span>
        <span>
            <a href="{{ route('legal', 'impressum') }}">{{ __('Impressum') }}</a>
            <a href="{{ route('legal', 'datenschutz') }}">{{ __('Datenschutz') }}</a>
            <a href="{{ route('legal', 'agb') }}">{{ __('AGB') }}</a>
            <a href="mailto:{{ config('signo.company.email') }}">{{ __('Kontakt') }}</a>
        </span>
        <span class="langs" aria-label="{{ __('Sprache') }}">
            @foreach (\App\Http\Middleware\SetLocale::LOCALES as $lc => $ll)
                <a href="?sprache={{ $lc }}" @if (app()->getLocale() === $lc) aria-current="true" @endif>{{ $ll }}</a>
            @endforeach
        </span>
    </div>
</footer>

@livewireScripts
</body>
</html>
