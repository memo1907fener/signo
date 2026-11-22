<!DOCTYPE html>
<html lang="de">
<head>
    <x-head />
    @livewireStyles
    <meta name="description" content="Foto der Speisekarte oder Preisliste hochladen – Signo macht daraus in Minuten eine Anzeige für Ihren Bildschirm und einen QR-Code fürs Handy. 14 Tage kostenlos.">
</head>
<body class="font-sans">

<header class="sticky top-0 z-30 bg-paper/85 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <a href="/"><x-logo /></a>
        <nav class="hidden items-center gap-7 text-sm font-medium text-ink/70 md:flex">
            <a href="#so-gehts" class="hover:text-ink">So geht's</a>
            <a href="#branchen" class="hover:text-ink">Branchen</a>
            <a href="#preise" class="hover:text-ink">Preise</a>
            <a href="#fragen" class="hover:text-ink">Fragen</a>
        </nav>
        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('pages.index') }}" class="btn-primary">Zu meinen Inhalten</a>
            @else
                <a href="{{ route('login') }}" class="btn hidden text-ink/70 hover:text-ink sm:inline-flex">Anmelden</a>
                <a href="{{ route('register') }}" class="btn-brand">Kostenlos testen</a>
            @endauth
        </div>
    </div>
</header>

{{-- Hero --}}
<section class="mx-auto grid max-w-6xl items-center gap-12 px-4 pb-20 pt-12 sm:px-6 lg:grid-cols-[1fr_1.1fr] lg:pt-20">
    <div>
        <span class="chip bg-white text-ink ring-1 ring-black/10">✨ Mit KI – ohne Grafikkenntnisse</span>
        <h1 class="mt-5 text-[2.6rem] font-extrabold leading-[1.02] tracking-tight sm:text-6xl">
            Vom Foto zum Bildschirm in <span class="text-brand">zwei Minuten</span>.
        </h1>
        <p class="mt-6 max-w-xl text-lg text-ink/70">
            Fotografieren Sie Ihre Speisekarte, Preisliste oder Ihr Angebot. Signo macht daraus eine schöne Anzeige für Ihren Fernseher – und einen QR-Code fürs Handy gleich dazu.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('register') }}" class="btn-brand px-7 py-3.5 text-base">{{ config('signo.trial_days') }} Tage kostenlos testen</a>
            <a href="#so-gehts" class="btn-ghost px-7 py-3.5 text-base">So funktioniert's</a>
        </div>
        <p class="mt-4 text-sm text-muted">Keine Kreditkarte · Keine Installation · Endet automatisch</p>
    </div>

    <div x-data="{ i: 0, n: {{ $demo->count() }} }" x-init="setInterval(() => i = (i + 1) % n, 3800)">
        <div class="relative rounded-[1.6rem] bg-ink p-2.5 shadow-2xl shadow-black/25 sm:p-3">
            <div class="relative aspect-video overflow-hidden rounded-xl">
                @foreach ($demo as $k => $p)
                    <div class="sg-stage absolute inset-0 transition-opacity duration-700" :class="i === {{ $k }} ? 'opacity-100' : 'opacity-0'">
                        @include('display.page', ['page' => $p, 'mode' => 'tv'])
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mx-auto h-6 w-28 bg-gradient-to-b from-ink/80 to-ink/40" style="clip-path: polygon(20% 0, 80% 0, 100% 100%, 0 100%)"></div>
        <div class="mt-5 flex justify-center gap-2">
            @foreach ($demo as $k => $p)
                <button @click="i = {{ $k }}" class="h-2 rounded-full transition-all" :class="i === {{ $k }} ? 'w-6 bg-ink' : 'w-2 bg-ink/20'" aria-label="Beispiel {{ $k + 1 }}"></button>
            @endforeach
        </div>
    </div>
</section>

{{-- So geht's --}}
<section id="so-gehts" class="bg-white py-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">So einfach geht's</h2>
        <p class="mt-2 text-lg text-muted">Drei Schritte. Kein Handbuch.</p>
        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['📷', 'Foto hochladen', 'Speisekarte, Preisliste, Plakat oder einfach Text – als Foto, PDF oder getippt.'],
                ['✨', 'Signo gestaltet', 'Die KI überträgt alle Einträge und Preise in eine fertige Vorlage. Hell oder dunkel, mit Ihrer Farbe.'],
                ['📺', 'Am Bildschirm zeigen', 'Fernseher verbinden, Code eingeben – fertig. Änderungen erscheinen in Sekunden, auch vom Handy aus.'],
            ] as $n => [$icon, $title, $text])
                <div class="rounded-3xl bg-paper p-7">
                    <div class="flex items-center justify-between">
                        <span class="text-3xl">{{ $icon }}</span>
                        <span class="text-sm font-bold text-ink/25">0{{ $n + 1 }}</span>
                    </div>
                    <h3 class="mt-6 text-xl font-bold">{{ $title }}</h3>
                    <p class="mt-2 text-ink/70">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Branchen --}}
<section id="branchen" class="py-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Passt zu Ihrem Betrieb</h2>
        <p class="mt-2 text-lg text-muted">Mit fertigen Beispielseiten für Ihre Branche – Sie ändern nur noch Texte und Preise.</p>
        <div class="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3">
            @php
                $examples = [
                    'gastro' => 'Speisekarte, Mittagsmenü, Tagesangebot',
                    'beauty' => 'Preisliste, Aktionen, Öffnungszeiten',
                    'retail' => 'Angebot der Woche, Neuheiten, Service',
                    'health' => 'Willkommen, Ordinationszeiten, Leistungen',
                    'office' => 'Empfang, Tagesprogramm, WLAN-Info',
                    'other'  => 'Alles, was Ihre Kunden sehen sollen',
                ];
            @endphp
            @foreach (config('signo.industries') as $key => $ind)
                <a href="{{ route('register', ['branche' => $key]) }}" class="group rounded-3xl bg-white p-6 ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-lg">
                    <span class="text-3xl">{{ $ind['icon'] }}</span>
                    <h3 class="mt-4 font-bold">{{ $ind['label'] }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ $examples[$key] }}</p>
                    <span class="mt-4 inline-block text-sm font-semibold text-brand opacity-0 transition group-hover:opacity-100">Jetzt testen →</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- Vorteile --}}
<section class="bg-ink py-20 text-white">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Alles drin. Nichts zu viel.</h2>
        <p class="mt-2 text-lg text-white/60">Alle Funktionen sind in jedem Paket enthalten.</p>
        <div class="mt-12 grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Ändern per Sprache', '„Schnitzel kostet jetzt 19,90“ – ins Handy sagen, Vorschau prüfen, fertig. Kein Tippen in Formularen.'],
                ['16 Sprachen automatisch', 'Gäste sehen Ihre Karte in ihrer Sprache. Am Bildschirm wechseln die Sprachen auf Wunsch ab.'],
                ['Bestellen per QR-Code', 'Gäste bestellen am Handy, die Küche sieht es sofort am Tablet. Die Abholnummern erscheinen am Bildschirm.'],
                ['Hell & dunkel', 'Jede Seite als helle oder dunkle Variante, mit Ihrer Akzentfarbe und passender Schrift.'],
                ['QR-Code inklusive', 'Jede Seite gibt es auch fürs Handy. QR-Code ausdrucken, auf den Tisch stellen – immer aktuell.'],
                ['Passt immer', 'Viel Text? Die Schrift passt sich automatisch an, damit alles auf den Bildschirm passt. Auch im Hochformat.'],
                ['Läuft auch offline', 'Fällt das Internet aus, zeigt der Bildschirm einfach den letzten Stand weiter.'],
                ['Feste Betriebszeiten', 'Morgens an, abends schwarz – ganz automatisch. Spart Strom und schont das Gerät.'],
                ['Jeder Fernseher', 'Smart-TV mit Browser, günstige Android-TV-Box oder unsere App. Ohne Vertrag, ohne Techniker.'],
            ] as [$t, $d])
                <div>
                    <div class="h-1 w-10 rounded-full bg-brand"></div>
                    <h3 class="mt-4 text-lg font-bold">{{ $t }}</h3>
                    <p class="mt-2 text-white/65">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Preise --}}
<section id="preise" class="py-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Faire Preise</h2>
        <p class="mt-2 text-lg text-muted">{{ config('signo.trial_days') }} Tage alles kostenlos testen. Danach monatlich kündbar.</p>
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            @foreach (config('signo.plans') as $key => $p)
                @php $hl = $key === 'screen'; @endphp
                <div class="relative flex flex-col rounded-3xl p-7 {{ $hl ? 'bg-ink text-white shadow-xl' : 'bg-white ring-1 ring-black/5' }}">
                    @if ($hl)<span class="chip absolute -top-3 left-7 bg-brand text-white">Am beliebtesten</span>@endif
                    <h3 class="font-bold">{{ $p['name'] }}</h3>
                    <p class="mt-4"><span class="text-4xl font-extrabold tracking-tight">{{ number_format($p['price'] / 100, 0, ',', '.') }} €</span>
                        <span class="{{ $hl ? 'text-white/60' : 'text-muted' }}">/ {{ $p['interval'] === 'year' ? 'Jahr' : 'Monat' }}</span></p>
                    <ul class="mt-6 flex-1 space-y-2.5 text-sm">
                        @foreach ($p['features'] as $f)
                            <li class="flex gap-2"><span class="text-brand">✓</span><span>{{ $f }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-7 {{ $hl ? 'btn-brand' : 'btn-primary' }} py-3">Kostenlos testen</a>
                </div>
            @endforeach
        </div>
        <p class="mt-6 text-sm text-muted">Alle Preise inkl. USt. Bezahlung per Karte, SEPA-Lastschrift, Apple Pay oder Google Pay.</p>
    </div>
</section>

{{-- Fragen --}}
<section id="fragen" class="bg-white py-20">
    <div class="mx-auto max-w-3xl px-4 sm:px-6">
        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Häufige Fragen</h2>
        <div class="mt-10 divide-y divide-black/10">
            @foreach ([
                ['Welchen Fernseher brauche ich?', 'Jeden Fernseher oder Monitor mit Internet-Browser – oder eine günstige Android-TV-Box am HDMI-Anschluss. Dort öffnen Sie ' . str_replace(['https://', 'http://'], '', url('/tv')) . ' und geben den angezeigten Code in Ihrem Konto ein.'],
                ['Was passiert nach der Testphase?', 'Nichts, solange Sie nichts tun: Die Testphase endet automatisch, es wird nichts abgebucht. Wählen Sie ein Paket, laufen Ihre Inhalte nahtlos weiter.'],
                ['Kann ich jederzeit kündigen?', 'Ja. Monatspakete sind monatlich kündbar, mit einem Klick in Ihrem Konto.'],
                ['Was ist, wenn das Internet ausfällt?', 'Der Bildschirm spielt den zuletzt geladenen Stand weiter und holt sich Änderungen, sobald die Verbindung wieder da ist.'],
                ['Wie funktioniert das Bestellen?', 'Gäste scannen den QR-Code, legen Gerichte in den Warenkorb und schicken die Bestellung ab. Die Küche sieht sie sofort am Tablet und meldet „Fertig“ – der Gast sieht das am Handy, die Nummer erscheint auf dem Bildschirm. Bezahlt wird wie gewohnt vor Ort.'],
                ['Welche Sprachen gibt es?', 'Englisch, Italienisch, Französisch, Spanisch, Niederländisch, Tschechisch, Slowakisch, Ungarisch, Slowenisch, Kroatisch, Polnisch, Türkisch, Ukrainisch, Russisch, Chinesisch und Japanisch. Die Übersetzung erstellt die KI, Preise bleiben unverändert.'],
                ['Stimmen die Preise nach dem KI-Import?', 'Die KI überträgt Einträge und Preise sehr genau. Trotzdem sehen Sie danach alles in einer Vorschau und können jede Zeile prüfen und ändern, bevor sie erscheint.'],
                ['Wie steht es um den Datenschutz?', 'Signo setzt keine Werbe- oder Tracking-Cookies, Schriften werden lokal geladen. Ihre Daten nutzen wir nur, um Ihre Anzeigen bereitzustellen.'],
            ] as [$q, $a])
                <details class="group py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold">
                        {{ $q }}<span class="text-2xl font-light text-muted transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-3 text-ink/70">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

<section class="py-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="rounded-[2rem] bg-brand px-6 py-14 text-center text-white sm:px-12">
            <h2 class="text-3xl font-extrabold tracking-tight sm:text-5xl">Heute noch auf dem Bildschirm.</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-white/85">Konto anlegen, Foto hochladen, Fernseher verbinden. Mehr ist es nicht.</p>
            <a href="{{ route('register') }}" class="btn mt-8 bg-white px-8 py-3.5 text-base text-ink hover:bg-paper">{{ config('signo.trial_days') }} Tage kostenlos testen</a>
        </div>
    </div>
</section>

<footer class="border-t border-black/5 py-10">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 text-sm text-muted sm:px-6">
        <x-logo />
        <div class="flex flex-wrap gap-5">
            <a href="{{ route('legal', 'impressum') }}" class="hover:text-ink">Impressum</a>
            <a href="{{ route('legal', 'datenschutz') }}" class="hover:text-ink">Datenschutz</a>
            <a href="{{ route('legal', 'agb') }}" class="hover:text-ink">AGB</a>
            <a href="mailto:{{ config('signo.company.email') }}" class="hover:text-ink">Kontakt</a>
        </div>
        <p>© {{ date('Y') }} {{ config('signo.company.name') }}</p>
    </div>
</footer>

@livewireScripts
</body>
</html>
