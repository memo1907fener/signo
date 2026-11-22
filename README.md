# Signo

Digitale Anzeigen ohne Aufwand: Foto der Speisekarte oder Preisliste hochladen – Signo macht
daraus eine Anzeige für den Bildschirm und eine Handy-Seite mit QR-Code.

Der Kunde sieht nur drei Bereiche: **Inhalte**, **Bildschirme**, **Konto**.

| Bereich | Was passiert dort |
| --- | --- |
| Inhalte | Seiten anlegen (KI-Import aus Foto/PDF/Text oder leere Vorlage), bearbeiten, QR-Code |
| Bildschirme | Fernseher per 6-stelligem Code verbinden, Seiten wählen, Anzeigedauer, Betriebszeiten |
| Bestellungen | Bestellen per QR-Code, Küchenansicht, Abholanzeige (für Gastro sichtbar, sonst nach dem Einschalten) |
| Konto | Paket wählen (Stripe), Rechnungen, Standorte, Team einladen, Firmendaten, Passwort, Standort löschen |

Weitere Funktionen im Seiten-Editor:

- **Schnell ändern:** Änderungswunsch sprechen oder tippen („Schnitzel 19,90, Gulasch streichen“), die KI
  zeigt die Änderungen als Vorschau, ein Klick übernimmt sie.
- **Weitere Sprachen:** bis zu 6 von 16 Sprachen je Seite. Die Handy-Seite wählt die Sprache des Gastes
  automatisch, am Bildschirm können die Sprachen abwechseln. Geänderte Texte werden auf Knopfdruck nachübersetzt.
- **Zeitplan:** Seite nur an bestimmten Tagen, Uhrzeiten oder in einem Zeitraum zeigen.
- **Laufschrift** am unteren Bildschirmrand; neue Seitentypen **Uhr & Wetter** und **Countdown**.
- **Texte vorschlagen** (KI) und **Fotobibliothek** (Pexels, Schlüssel `PEXELS_API_KEY` in der `.env`).
- **Per Handy bestellbar:** Einträge mit Preis bekommen auf der Handy-Seite einen „+“-Knopf. Preise rechnet
  immer der Server aus den gespeicherten Daten. Bezahlt wird vor Ort.

Technik: Laravel 13, Livewire 4, Tailwind 4, MariaDB/MySQL, PHP 8.3+ (empfohlen 8.4).
Die fertig gebauten Oberflächen-Dateien (`public/build`) liegen im Repository – auf dem Server
wird **kein Node/npm** gebraucht.

---

## Installation auf dem Server (aaPanel)

### 1. Website und Datenbank anlegen

1. aaPanel → **Website** → **Add site**: Domain `signo.at` (und `www.signo.at`), PHP **8.4**,
   Datenbank **MySQL** anlegen lassen (Name z. B. `signo`) – Zugangsdaten notieren.
2. In den Website-Einstellungen:
   - **Site directory** → *Running directory* auf `/public` stellen, speichern.
   - **URL rewrite** → Vorlage `laravel5` wählen, speichern.
   - **SSL** → Let's Encrypt für `signo.at` und `www.signo.at`, *Force HTTPS* einschalten.
3. **App Store → PHP 8.4 → Settings**:
   - *Configuration modify*: `upload_max_filesize = 110M`, `post_max_size = 120M`, `max_execution_time = 180`
   - *Disabled functions*: `putenv`, `proc_open` und `symlink` **entfernen** (Laravel braucht sie).
   - Unter *Install extensions* sicherstellen, dass `fileinfo` und `exif` installiert sind.

### 2. Code holen

Im Terminal (aaPanel → Terminal):

```bash
cd /www/wwwroot/signo.at
chattr -i .user.ini 2>/dev/null; rm -f .user.ini index.html 404.html .htaccess
git clone https://github.com/memo1907fener/signo.git .
```

### 3. Installieren

```bash
bash deploy/install.sh
```

Beim ersten Mal legt das Skript die Datei `.env` an und stoppt. Diese öffnen
(aaPanel → Files → `/www/wwwroot/signo.at/.env`) und eintragen:

- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` – aus Schritt 1
- `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` – für „Passwort vergessen“ und Erinnerungen
- `SIGNO_ADMIN_EMAILS` – Ihre E-Mail-Adresse (wird beim Registrieren Admin)
- `SIGNO_COMPANY_…` – Firmendaten für Impressum und Datenschutz

Dann noch einmal:

```bash
bash deploy/install.sh
```

### 4. Cronjob

aaPanel → **Cron** → *Shell Script*, jede Minute:

```bash
cd /www/wwwroot/signo.at && php artisan schedule:run >> /dev/null 2>&1
```

Damit laufen: Erinnerungs-Mails vor Ende der Testphase und das nächtliche Aufräumen.

### 5. Testen

1. `https://signo.at` öffnen → **Kostenlos testen** → registrieren (mit der Admin-Adresse).
2. Auf einem zweiten Gerät `https://signo.at/tv` öffnen → Code im Kundenbereich unter
   **Bildschirme** eingeben.

---

## KI-Import einschalten (Claude-API)

1. Unter <https://console.anthropic.com> einen API-Schlüssel erstellen und Guthaben aufladen.
2. In der `.env` eintragen (niemals in einen Chat oder ins Repository):

   ```
   ANTHROPIC_API_KEY=sk-ant-...
   ```

3. `php artisan optimize` ausführen.

Ohne Schlüssel bleibt der KI-Import ausgeblendet; Kunden starten dann mit leeren Vorlagen.
`SIGNO_AI_DAILY_LIMIT` begrenzt die Importe pro Kunde und Tag (Standard 20).
Ein Import kostet je nach Umfang wenige Cent (Modell `claude-sonnet-5-5`, Aufwand `low`).

## Zahlungen einschalten (Stripe)

1. Im Stripe-Dashboard drei Produkte mit Preisen anlegen (Preise inkl. USt.):
   - *QR & Web* – 29 € jährlich
   - *Bildschirm* – 19 € monatlich
   - *Mehrere Bildschirme* – 39 € monatlich
2. Die Preis-IDs (`price_…`) in die `.env` eintragen: `STRIPE_PRICE_QR`, `STRIPE_PRICE_SCREEN`, `STRIPE_PRICE_MULTI`.
3. `STRIPE_SECRET` = geheimer Schlüssel (`sk_live_…`).
4. Webhook anlegen: URL `https://signo.at/stripe/webhook`, Ereignisse
   `checkout.session.completed`, `customer.subscription.created`, `customer.subscription.updated`,
   `customer.subscription.deleted` → Signatur-Geheimnis als `STRIPE_WEBHOOK_SECRET` eintragen.
5. Im Stripe-Dashboard das **Kundenportal** aktivieren (Kündigen, Zahlungsart ändern, Rechnungen)
   und unter *Einstellungen → E-Mails* den Versand von Rechnungen einschalten.
6. `php artisan optimize`.

Preise und Paketinhalte, die auf der Website stehen, werden in `config/signo.php` gepflegt.

## Updates einspielen

```bash
cd /www/wwwroot/signo.at && bash deploy/update.sh
```

## Android-App

Liegt im Ordner [`android/`](android/README.md) und wird automatisch über GitHub Actions gebaut.
Die fertige APK nach `storage/app/signo.apk` hochladen – dann ist sie unter
`https://signo.at/app/signo.apk` zum Installieren auf TV-Boxen erreichbar.

## Wichtige Adressen

| Adresse | Zweck |
| --- | --- |
| `/` | Startseite |
| `/registrieren`, `/anmelden` | Konto |
| `/inhalte`, `/bildschirme`, `/konto` | Kundenbereich |
| `/admin` | Plattform-Verwaltung (nur Admins) |
| `/tv` | Player für Fernseher und TV-Boxen |
| `/m/{code}` | Handy-Ansicht einer Seite (QR-Code), `?lang=en` für eine Sprache, `?tisch=5` für Tischbestellung |
| `/bestellungen` | Bestellungen und Einstellungen |
| `/kueche/{link}` | Küchenbildschirm ohne Anmeldung (geheimer Link) |
| `/abholung/{link}` | Abholanzeige mit Bestellnummern |

## Robustheit des Players

- Bildschirm verliert seine Kopplung nicht bei Serverfehlern, Neustarts oder Proxy-Problemen –
  nur wenn der Bildschirm im Kundenbereich wirklich entfernt wurde.
- Ohne Internet läuft der zuletzt geladene Stand weiter, Bilder und Videos kommen aus dem Speicher
  des Geräts (Service Worker).
- Selbstheilung bei hängender Wiedergabe, nächtlicher Neustart, Bildschirm bleibt wach.
- Betriebszeiten: außerhalb wird der Bildschirm schwarz geschaltet.

## Rechtliches

Impressum, Datenschutzerklärung und AGB sind als Vorlage enthalten (`resources/views/site/legal.blade.php`)
und ziehen die Firmendaten aus der `.env`. Bitte vor dem Start rechtlich prüfen lassen.
