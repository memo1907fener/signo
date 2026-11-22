@php
    $plans = config('signo.plans');
    $euro = fn ($c) => number_format($c / 100, 0, ',', '.') . ' €';
    $tvHost = str_replace(['https://', 'http://'], '', url('/tv'));
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Signo – Vom Foto zum Bildschirm</title>
    <meta name="description" content="Speisekarte, Preisliste oder Angebot fotografieren – Signo macht daraus in Minuten eine Anzeige für Ihren Fernseher, eine Handy-Seite mit QR-Code und auf Wunsch Bestellungen per Handy. 14 Tage kostenlos.">
    <meta name="theme-color" content="#0f1631">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/site.css', 'resources/js/site.js'])
    @livewireStyles
</head>
<body class="site">

<header class="top">
    <div class="wrap">
        <a href="/" class="logo" aria-label="Signo Startseite">
            <svg viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="9" fill="#ff5a36"/><rect x="7" y="9" width="18" height="12" rx="2.5" fill="none" stroke="#fff" stroke-width="2.4"/><circle cx="16" cy="25" r="2" fill="#fff"/></svg>
            signo
        </a>
        <nav aria-label="Hauptnavigation">
            <a href="#ausprobieren">Ausprobieren</a>
            <a href="#branchen">Branchen</a>
            <a href="#funktionen">Funktionen</a>
            <a href="#preise">Preise</a>
            <a href="#fragen">Fragen</a>
        </nav>
        @auth
            <a href="{{ route('pages.index') }}" class="b b-signal">Zu meinen Inhalten</a>
        @else
            <a href="{{ route('login') }}" class="login" style="text-decoration:none;opacity:.8">Anmelden</a>
            <a href="{{ route('register') }}" class="b b-signal">Kostenlos testen</a>
        @endauth
    </div>
</header>

{{-- ===== Hero ===== --}}
<section class="hero">
    <div class="wrap hero-grid">
        <div>
            <h1 class="d1">Vom Foto zum Bildschirm.</h1>
            <p class="lead">Fotografieren Sie Ihre Speisekarte, Preisliste oder Ihr Angebot. Signo macht daraus in zwei Minuten eine Anzeige für den Fernseher im Geschäft – und eine Handy-Seite mit QR-Code gleich dazu.</p>
            <div class="ctas">
                <a href="{{ route('register') }}" class="b b-signal">{{ config('signo.trial_days') }} Tage kostenlos testen</a>
                <a href="#ausprobieren" class="b b-quiet">Jetzt ohne Anmeldung ausprobieren</a>
            </div>
            <p class="fine">Keine Kreditkarte. Kein Gerätekauf. Endet automatisch.</p>
        </div>

        <div class="stage" x-data="signoHero({{ count($heroPages) }})" aria-label="Beispiel: ein Fernseher zeigt eine Speisekarte, daneben die Handy-Ansicht">
            <div class="tv3d">
                <div class="tv">
                    <div class="tv-screen">
                        @foreach ($heroPages as $k => $p)
                            <div class="sg-stage" :class="{ on: i === {{ $k }} }" @class(['on' => $k === 0])>
                                @include('display.page', ['page' => $p, 'mode' => 'tv'])
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="tv-foot"></div>
            </div>
            <div class="paper" aria-hidden="true">
                <b>Speisekarte</b>
                <i><span>Frittatensuppe</span><span>4,90</span></i>
                <i><span>Wiener Schnitzel</span><span>18,90</span></i>
                <i><span>Käsespätzle</span><span>12,50</span></i>
                <i><span>Kaiserschmarrn</span><span>8,90</span></i>
            </div>
            <div class="phone" aria-hidden="true">
                <div class="phone-notch"></div>
                <div class="phone-screen"><div class="phone-scale">@include('display.page', ['page' => $heroPages[0], 'mode' => 'mobile'])</div></div>
            </div>
        </div>
    </div>
</section>

{{-- ===== Live-Demo ===== --}}
<section class="sec demo" id="ausprobieren">
    <div class="wrap">
        <div class="sec-head">
            <h2 class="d2">Probieren Sie es gleich hier.</h2>
            <p class="lead muted">Tippen Sie Ihre Karte so, wie sie Ihnen einfällt – ein Eintrag pro Zeile, Preis ans Ende. Rechts sehen Sie sofort, wie es am Fernseher und am Handy aussieht.</p>
        </div>
        <div class="demo-grid" x-data="signoDemo(@js($demoText))">
            <div class="demo-editor">
                <label for="demo-text" class="sr">Ihre Karte</label>
                <textarea id="demo-text" x-model="text" spellcheck="false"></textarea>
                <div class="demo-tools">
                    <div class="seg" role="group" aria-label="Hell oder dunkel">
                        <button type="button" @click="theme = 'light'" :aria-pressed="theme === 'light'">Hell</button>
                        <button type="button" @click="theme = 'dark'" :aria-pressed="theme === 'dark'">Dunkel</button>
                    </div>
                    <div class="seg" role="group" aria-label="Schrift">
                        <button type="button" @click="font = 'modern'" :aria-pressed="font === 'modern'">Modern</button>
                        <button type="button" @click="font = 'elegant'" :aria-pressed="font === 'elegant'" style="font-family:'Fraunces Variable',Georgia,serif">Elegant</button>
                    </div>
                    <div class="dots" role="group" aria-label="Farbe">
                        @foreach (config('signo.accents') as $key => $hex)
                            <button type="button" @click="accent = '{{ $key }}'" :aria-pressed="accent === '{{ $key }}'" style="background: {{ $hex }}" aria-label="Farbe {{ $key }}"></button>
                        @endforeach
                    </div>
                </div>
                @if ($aiDemo)
                    <p style="margin-top:18px">
                        <label class="b b-quiet photo-btn" :style="busy && 'opacity:.6'">
                            <input type="file" accept="image/*,application/pdf" class="sr" @change="photo($event)" :disabled="busy">
                            <span x-text="busy ? 'Die KI liest Ihre Karte …' : 'Oder Foto Ihrer Karte hochladen'"></span>
                        </label>
                    </p>
                @endif
                <p x-show="error" x-text="error" style="color:#c2311a;margin-top:10px" x-cloak></p>
            </div>
            <div class="demo-out" data-reveal>
                <div class="tv"><div class="tv-screen"><div class="sg-stage on" x-ref="tv"></div></div></div>
                <div class="phone demo-phone" style="transform:none">
                    <div class="phone-notch"></div>
                    <div class="phone-screen"><div class="phone-scale" x-ref="phone"></div></div>
                </div>
                <p class="demo-hint">Gefällt es Ihnen? <a href="{{ route('register') }}" style="font-weight:600;color:var(--signal)">Konto anlegen</a> – Ihre Karte ist in zwei Minuten auf Ihrem Fernseher.</p>
            </div>
        </div>
    </div>
</section>

{{-- ===== Branchen ===== --}}
<section class="sec ind" id="branchen" x-data="signoIndustries('gastro')">
    <div class="wrap">
        <div class="sec-head">
            <h2 class="d2">Fertige Seiten für Ihre Branche.</h2>
            <p class="lead muted">Nach der Anmeldung warten diese Beispiele schon auf Sie. Sie ändern nur Texte und Preise.</p>
        </div>
        <div class="tabs" role="tablist">
            @foreach ($industries as $key => $ind)
                <button type="button" role="tab" @click="pick('{{ $key }}')" :aria-selected="ind === '{{ $key }}'">{{ $ind['icon'] }} {{ $ind['label'] }}</button>
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
                            <button type="button" class="mini" @click="go({{ $n }})" :aria-current="page === {{ $n }}" aria-label="{{ $p->title }} zeigen">
                                <span class="sg-stage" style="position:absolute;inset:0">@include('display.page', ['page' => $p, 'mode' => 'tv'])</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="phone-col">
                    <div class="phone" style="position:relative;right:auto;bottom:auto;width:100%;transform:none">
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

{{-- ===== So geht's (echte Reihenfolge) ===== --}}
<section class="sec">
    <div class="wrap">
        <div class="sec-head"><h2 class="d2">Drei Schritte, kein Handbuch.</h2></div>
        <div class="steps">
            <div class="step"><div class="n">1</div><h3 class="d3">Karte hochladen</h3><p>Foto, PDF oder einfach eintippen. Die KI überträgt alle Einträge und Preise.</p></div>
            <div class="step"><div class="n">2</div><h3 class="d3">Aussehen wählen</h3><p>Hell oder dunkel, Ihre Farbe, moderne oder elegante Schrift. Die Größe passt sich von selbst an.</p></div>
            <div class="step"><div class="n">3</div><h3 class="d3">Fernseher verbinden</h3><p>Am Fernseher {{ $tvHost }} öffnen, den Code eingeben – läuft. Änderungen erscheinen in Sekunden.</p></div>
        </div>
    </div>
</section>

{{-- ===== Funktionen mit lebenden Beispielen ===== --}}
<section class="sec feat" id="funktionen">
    <div class="wrap">
        <div class="sec-head">
            <h2 class="d2">Mehr als eine Speisekarte.</h2>
            <p class="lead muted">Alle Funktionen sind in jedem Paket enthalten.</p>
        </div>
        <div class="feat-list">
            <div class="feat-item"><div><h3 class="d3">Ändern per Sprache</h3><p>„Schnitzel kostet jetzt 19,90.“ Ins Handy sagen, Vorschau prüfen, übernehmen.</p></div>
                <div class="viz viz-voice" aria-hidden="true">@for ($i = 0; $i < 9; $i++)<i></i>@endfor</div></div>
            <div class="feat-item"><div><h3 class="d3">16 Sprachen automatisch</h3><p>Gäste sehen die Karte in ihrer Sprache. Am Bildschirm wechseln die Sprachen auf Wunsch ab.</p></div>
                <div class="viz viz-lang" aria-hidden="true"><span>Speisekarte</span><span>Menu</span><span>Menù</span><span>Carte</span></div></div>
            <div class="feat-item"><div><h3 class="d3">Bestellen per QR-Code</h3><p>Gäste bestellen am Handy, die Küche sieht es am Tablet, die Abholnummer erscheint am Bildschirm.</p></div>
                <div class="viz viz-order" aria-hidden="true">#27 ✓</div></div>
            <div class="feat-item"><div><h3 class="d3">Uhr, Wetter, Countdown</h3><p>Live-Bausteine für Empfang und Wartebereich, dazu eine Laufschrift für kurze Hinweise.</p></div>
                <div class="viz viz-clock" aria-hidden="true" data-clock>{{ now()->format('H:i') }}</div></div>
            <div class="feat-item"><div><h3 class="d3">Zeitpläne</h3><p>Frühstückskarte bis 11 Uhr, Mittagsmenü nur Mo–Fr, Adventaktion vom 1. bis 24. Dezember.</p></div>
                <div class="viz viz-ticker" aria-hidden="true"><span>Mo–Fr 11:30–14:00 · Mittagsmenü · Sa–So Brunch</span></div></div>
            <div class="feat-item"><div><h3 class="d3">Läuft auch ohne Internet</h3><p>Fällt die Verbindung aus, zeigt der Bildschirm den letzten Stand weiter – samt Bildern und Videos.</p></div>
                <div class="viz viz-offline" aria-hidden="true">📶✕ ▶</div></div>
            <div class="feat-item"><div><h3 class="d3">Team und Standorte</h3><p>Mitarbeiter einladen, mehrere Filialen in einem Konto, jeder Standort mit eigenen Bildschirmen.</p></div>
                <div class="viz" aria-hidden="true" style="font-size:1.9rem">👥 🏬</div></div>
            <div class="feat-item"><div><h3 class="d3">Texte und Fotos per Klick</h3><p>Ein paar Stichworte genügen: Signo schlägt Texte für Ihr Angebot vor und findet passende Fotos.</p></div>
                <div class="viz" aria-hidden="true" style="font-size:1.9rem">✍️ 📷</div></div>
        </div>
    </div>
</section>

{{-- ===== Preise ===== --}}
<section class="sec" id="preise">
    <div class="wrap">
        <div class="sec-head">
            <h2 class="d2">Was kostet das für Sie?</h2>
            <p class="lead muted">{{ config('signo.trial_days') }} Tage alles kostenlos. Danach monatlich kündbar, Preise inkl. USt.</p>
        </div>
        <div class="price-grid">
            <div class="calc" x-data="signoCalc(@js($plans))">
                <label for="screens">Wie viele Bildschirme haben Sie?</label>
                <input id="screens" type="range" min="0" max="10" step="1" x-model.number="n">
                <div class="scale"><span>nur QR & Handy</span><span>1</span><span>5</span><span>10</span></div>
                <p class="big">
                    <template x-if="plan.price"><span x-text="euro(plan.price)"></span></template>
                    <template x-if="!plan.price"><span>Auf Anfrage</span></template>
                    <small style="font-size:1.1rem;font-weight:500;color:#5b6480" x-show="plan.price" x-text="plan.interval === 'year' ? 'pro Jahr' : 'pro Monat'"></small>
                </p>
                <p class="muted" style="margin:0"><strong x-text="plan.name" style="color:var(--ink)"></strong><span x-show="perScreen() && n > 1" x-text="' · ' + perScreen() + ' je Bildschirm'"></span></p>
                <ul><template x-for="f in plan.features" :key="f"><li x-text="f"></li></template></ul>
                <a :href="plan.key === 'custom' ? 'mailto:{{ config('signo.company.email') }}' : '{{ route('register') }}'" class="b b-signal" style="width:100%" x-text="plan.key === 'custom' ? 'Angebot anfragen' : 'Kostenlos starten'"></a>
            </div>

            <div class="cmp-wrap">
                <table class="cmp">
                    <thead><tr><th></th><th>Signo</th><th>Gedruckte Karte</th><th>Klassische Signage-Software</th></tr></thead>
                    <tbody>
                        <tr><th>Preis ändern</th><td>Sekunden, auch per Sprache</td><td>Neu drucken</td><td>Layout öffnen, bearbeiten, veröffentlichen</td></tr>
                        <tr><th>Gestaltung</th><td>Fertig aus dem Foto</td><td>Grafiker oder Copyshop</td><td>Selbst gestalten</td></tr>
                        <tr><th>Handy & QR-Code</th><td>Inklusive</td><td class="no">–</td><td class="no">meist eigenes Produkt</td></tr>
                        <tr><th>Mehrsprachig</th><td>16 Sprachen automatisch</td><td>Je Sprache drucken</td><td>Je Sprache gestalten</td></tr>
                        <tr><th>Bestellen per Handy</th><td>Inklusive</td><td class="no">–</td><td class="no">–</td></tr>
                        <tr><th>Gerät</th><td>Jeder Fernseher mit Browser oder Android-Box</td><td class="no">–</td><td>oft eigener Player nötig</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ===== Fragen ===== --}}
<section class="sec faq" id="fragen" style="background:#fff">
    <div class="wrap" style="max-width:860px">
        <div class="sec-head"><h2 class="d2">Häufige Fragen</h2></div>
        @foreach ([
            ['Welchen Fernseher brauche ich?', 'Jeden Fernseher oder Monitor mit Internet-Browser – oder eine günstige Android-TV-Box am HDMI-Anschluss. Dort ' . $tvHost . ' öffnen und den angezeigten Code in Ihrem Konto eingeben.'],
            ['Was passiert nach der Testphase?', 'Nichts, solange Sie nichts tun: Die Testphase endet automatisch, es wird nichts abgebucht. Wählen Sie ein Paket, laufen Ihre Inhalte nahtlos weiter.'],
            ['Wie funktioniert das Bestellen?', 'Gäste scannen den QR-Code, legen Gerichte in den Warenkorb und schicken die Bestellung ab. Die Küche sieht sie sofort am Tablet und meldet „Fertig“ – der Gast sieht das am Handy, die Nummer erscheint auf dem Bildschirm. Bezahlt wird wie gewohnt vor Ort.'],
            ['Welche Sprachen gibt es?', 'Englisch, Italienisch, Französisch, Spanisch, Niederländisch, Tschechisch, Slowakisch, Ungarisch, Slowenisch, Kroatisch, Polnisch, Türkisch, Ukrainisch, Russisch, Chinesisch und Japanisch.'],
            ['Was ist, wenn das Internet ausfällt?', 'Der Bildschirm spielt den zuletzt geladenen Stand weiter und holt sich Änderungen, sobald die Verbindung wieder da ist.'],
            ['Stimmen die Preise nach dem KI-Import?', 'Die KI überträgt Einträge und Preise sehr genau. Trotzdem sehen Sie danach alles in einer Vorschau und können jede Zeile prüfen, bevor sie erscheint.'],
            ['Kann ich mehrere Filialen verwalten?', 'Ja. Jeder Standort hat eigene Seiten und Bildschirme, Sie wechseln mit einem Klick. Mitarbeiter laden Sie per E-Mail ein.'],
            ['Wie steht es um den Datenschutz?', 'Signo setzt keine Werbe- oder Tracking-Cookies, Schriften werden von unserem Server geladen. Ihre Daten nutzen wir nur, um Ihre Anzeigen bereitzustellen.'],
        ] as [$q, $a])
            <details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
        @endforeach
    </div>
</section>

<section class="sec final">
    <div class="wrap">
        <h2 class="d1" style="font-size:clamp(2.6rem,6vw,5rem)">Heute noch auf dem Bildschirm.</h2>
        <a href="{{ route('register') }}" class="b">{{ config('signo.trial_days') }} Tage kostenlos testen</a>
    </div>
</section>

<footer class="foot">
    <div class="wrap">
        <span>© {{ date('Y') }} {{ config('signo.company.name') }}</span>
        <span>
            <a href="{{ route('legal', 'impressum') }}">Impressum</a>
            <a href="{{ route('legal', 'datenschutz') }}">Datenschutz</a>
            <a href="{{ route('legal', 'agb') }}">AGB</a>
            <a href="mailto:{{ config('signo.company.email') }}">Kontakt</a>
        </span>
    </div>
</footer>

@livewireScripts
</body>
</html>
