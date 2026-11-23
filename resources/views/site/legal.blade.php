<!DOCTYPE html>
<html lang="de">
<head><x-head :title="$title" /></head>
<body class="font-sans">
@php $c = config('signo.company'); $missing = blank($c['address']); @endphp
<header class="border-b border-black/5">
    <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-4 sm:px-6">
        <a href="/"><x-logo /></a>
        <a href="/" class="text-sm text-muted hover:text-ink">← Zur Startseite</a>
    </div>
</header>
<main class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <h1 class="text-3xl font-bold tracking-tight">{{ $title }}</h1>

    @if ($missing && auth()->user()?->is_admin)
        <div class="mt-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
            Hinweis für Admins: Bitte Firmendaten in der .env eintragen (SIGNO_COMPANY_NAME, SIGNO_COMPANY_ADDRESS, …) und die Texte rechtlich prüfen lassen.
        </div>
    @endif

    <div class="prose-signo mt-8 space-y-5 text-[15px] leading-relaxed text-ink/80 [&_h2]:mt-10 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-ink [&_a]:underline">
    @if ($doc === 'impressum')
        <p>Informationen gemäß § 5 E-Commerce-Gesetz, § 14 Unternehmensgesetzbuch und Offenlegung gemäß § 25 Mediengesetz:</p>
        <p><strong>{{ $c['name'] }}</strong><br>{!! nl2br(e(str_replace('\n', "\n", $c['address']))) !!}</p>
        <p>E-Mail: <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a>@if ($c['phone'])<br>Telefon: {{ $c['phone'] }}@endif</p>
        @if ($c['uid'])<p>UID-Nummer: {{ $c['uid'] }}</p>@endif
        @if ($c['register'])<p>{{ $c['register'] }}</p>@endif
        <p>Unternehmensgegenstand: Bereitstellung von Software für digitale Anzeigen (Software as a Service).</p>
        <p>Online-Streitbeilegung: Verbraucher haben die Möglichkeit, Beschwerden an die Online-Streitbeilegungsplattform der EU zu richten. Sie können allfällige Beschwerden auch an die oben angegebene E-Mail-Adresse richten.</p>

    @elseif ($doc === 'datenschutz')
        <p>Der Schutz Ihrer Daten ist uns wichtig. Wir verarbeiten personenbezogene Daten ausschließlich auf Grundlage der Datenschutz-Grundverordnung (DSGVO) und des österreichischen Datenschutzgesetzes.</p>
        <h2>Verantwortlicher</h2>
        <p>{{ $c['name'] }}@if ($c['address'])<br>{!! nl2br(e(str_replace('\n', "\n", $c['address']))) !!}@endif<br>E-Mail: <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></p>
        <h2>Welche Daten wir verarbeiten</h2>
        <p><strong>Kundenkonto:</strong> Name, E-Mail-Adresse, Name und Branche des Betriebs, verschlüsseltes Passwort. Zweck: Bereitstellung des Dienstes (Art. 6 Abs. 1 lit. b DSGVO).</p>
        <p><strong>Inhalte:</strong> Texte, Preise, Bilder und Videos, die Sie für Ihre Anzeigen hochladen. Diese werden auf Ihren Bildschirmen und – wenn Sie das wünschen – auf einer öffentlichen Handy-Seite angezeigt.</p>
        <p><strong>Bildschirme:</strong> Gerätekennung, IP-Adresse, Bildschirmauflösung und Zeitpunkt der letzten Verbindung, um den Betrieb zu überwachen und Störungen zu erkennen.</p>
        <p><strong>Server-Protokolle:</strong> IP-Adresse, Zeitpunkt und aufgerufene Adresse werden kurzfristig zur Abwehr von Missbrauch gespeichert (Art. 6 Abs. 1 lit. f DSGVO).</p>
        <h2>Cookies</h2>
        <p>Wir verwenden ausschließlich technisch notwendige Cookies (Sitzung, gewählte Sprache und Schutz vor Formular-Missbrauch). Nutzt ein Gast die digitale Stempelkarte eines Betriebs, speichert ein weiteres notwendiges Cookie die Nummer seiner Karte, damit sie beim nächsten Besuch wieder erscheint. Es gibt keine Werbe-, Analyse- oder Tracking-Cookies. Schriftarten werden von unserem eigenen Server geladen.</p>
        <h2>Auftragsverarbeiter</h2>
        <p><strong>Hosting:</strong> Unsere Server werden von einem Hosting-Dienstleister betrieben, mit dem ein Auftragsverarbeitungsvertrag besteht.</p>
        <p><strong>Zahlungen:</strong> Stripe Payments Europe Ltd., Dublin, Irland. Zahlungsdaten werden direkt bei Stripe eingegeben und nicht auf unseren Servern gespeichert.</p>
        <p><strong>KI-Import:</strong> Wenn Sie die Funktion „Mit KI erstellen“ nutzen, wird die hochgeladene Datei bzw. der Text an Anthropic PBC (USA) übermittelt, um die Inhalte auszulesen. Die Übermittlung erfolgt auf Grundlage der EU-Standardvertragsklauseln. Laden Sie dort bitte keine personenbezogenen Daten Dritter hoch. Ohne diese Funktion findet keine Übermittlung statt.</p>
        <p><strong>Übersetzungen und „Schnell ändern“:</strong> Wenn Sie Seiten übersetzen lassen oder Änderungen per Text/Sprache beauftragen, werden die Texte der Seite an Anthropic PBC (USA) übermittelt. Bei der Spracheingabe wandelt Ihr Browser die Sprache in Text um (je nach Browser über Google oder Apple); Signo erhält nur den Text.</p>
        <h2>Bestellungen von Gästen</h2>
        <p>Bestellt ein Gast über die Handy-Seite eines Betriebs, verarbeiten wir die Bestellung (Gerichte, angegebener Name oder Tischnummer, Anmerkung, IP-Adresse zum Schutz vor Missbrauch) im Auftrag dieses Betriebs. Verantwortlich ist der jeweilige Betrieb. Bestellungen werden nach 90 Tagen gelöscht.</p>
        <h2>Feedback, Stempelkarte und Statistik</h2>
        <p>Gibt ein Gast über die Feedback-Seite eines Betriebs eine Bewertung ab, verarbeiten wir die Sterne, den Text, freiwillig angegebene Kontaktdaten und die IP-Adresse (Schutz vor Missbrauch) im Auftrag dieses Betriebs. Die digitale Stempelkarte kommt ohne Namen aus: Gespeichert werden nur eine zufällige Kartennummer, die Anzahl der Stempel und der Zeitpunkt des letzten Stempels; Karten ohne Nutzung löschen wir nach 18 Monaten. Für die Statistik zählen wir je Seite und Tag, wie oft sie aufgerufen wurde – ohne Cookies, ohne IP-Adressen und ohne Bezug zu einzelnen Personen. Verantwortlich ist jeweils der Betrieb.</p>
        <p><strong>E-Mail-Versand:</strong> Systemnachrichten (z. B. Passwort zurücksetzen) werden über unseren E-Mail-Anbieter versendet.</p>
        <h2>Speicherdauer</h2>
        <p>Wir speichern Ihre Daten, solange Ihr Konto besteht. Wenn Sie Ihr Konto löschen, werden Inhalte, Bilder und Bildschirme sofort gelöscht. Rechnungsdaten bewahren wir gemäß den gesetzlichen Aufbewahrungsfristen (7 Jahre) auf.</p>
        <h2>Ihre Rechte</h2>
        <p>Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch. Schreiben Sie uns dazu an <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a>. Sie können sich außerdem bei der Österreichischen Datenschutzbehörde (www.dsb.gv.at) beschweren.</p>

    @else
        <h2>1. Geltungsbereich</h2>
        <p>Diese Bedingungen gelten für die Nutzung von Signo (nachfolgend „Dienst“), angeboten von {{ $c['name'] }}. Der Dienst richtet sich an Unternehmer.</p>
        <h2>2. Leistung</h2>
        <p>Signo ermöglicht das Erstellen von Inhalten und deren Anzeige auf Bildschirmen sowie als Handy-Seite. Der Umfang ergibt sich aus dem gewählten Paket. Bildschirme, Internetverbindung und sonstige Hardware stellt der Kunde selbst bereit.</p>
        <h2>3. Testphase</h2>
        <p>Neue Konten können den Dienst {{ config('signo.trial_days') }} Tage kostenlos testen. Die Testphase endet automatisch und verlängert sich nicht kostenpflichtig.</p>
        <h2>4. Preise und Zahlung</h2>
        <p>Es gelten die zum Zeitpunkt der Bestellung angegebenen Preise. Die Abrechnung erfolgt im Voraus für den gewählten Zeitraum über unseren Zahlungsdienstleister Stripe.</p>
        <h2>5. Laufzeit und Kündigung</h2>
        <p>Monatspakete verlängern sich jeweils um einen Monat, Jahrespakete um ein Jahr, sofern sie nicht vor Ablauf gekündigt werden. Die Kündigung ist jederzeit im Kundenkonto möglich und wirkt zum Ende des bezahlten Zeitraums.</p>
        <h2>6. Inhalte des Kunden</h2>
        <p>Der Kunde ist für seine Inhalte selbst verantwortlich und stellt sicher, dass er die nötigen Rechte an Texten, Bildern und Videos besitzt. Rechtswidrige Inhalte sind untersagt; wir dürfen solche Inhalte sperren. Vom KI-Import erzeugte Inhalte sind vor der Veröffentlichung vom Kunden zu prüfen, insbesondere Preise und Allergenangaben.</p>
        <h2>7. Verfügbarkeit</h2>
        <p>Wir bemühen uns um eine hohe Verfügbarkeit. Kurzfristige Unterbrechungen durch Wartung oder Störungen sind möglich; Bildschirme zeigen in diesem Fall den zuletzt geladenen Stand weiter.</p>
        <h2>8. Haftung</h2>
        <p>Wir haften nur für Vorsatz und grobe Fahrlässigkeit. Die Haftung für entgangenen Gewinn und Folgeschäden ist – soweit gesetzlich zulässig – ausgeschlossen.</p>
        <h2>9. Schlussbestimmungen</h2>
        <p>Es gilt österreichisches Recht unter Ausschluss der Verweisungsnormen. Gerichtsstand ist der Sitz von {{ $c['name'] }}.</p>
    @endif
    </div>
</main>
</body>
</html>
