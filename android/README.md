# Signo – Android-App für Bildschirme

Kiosk-App für TV-Boxen und Android-Fernseher. Zeigt `https://signo.at/tv` im Vollbild,
startet automatisch beim Einschalten und lässt sich aus dem Kundenbereich fernsteuern.

## Was die App kann

- Vollbild ohne Systemleisten, Zurück-Taste deaktiviert
- Startet automatisch beim Einschalten und nach Updates
- Bildschirm bleibt dauerhaft an
- Meldet sich selbst an; die Geräte-Kennung bleibt erhalten (kein doppeltes Koppeln)
- Fernbefehle aus dem Kundenbereich: Neu laden, App neu starten, Bildschirmfoto

### Selbstheilung

- Absturz der App → Neustart nach 3 Sekunden
- Absturz der Anzeige-Engine → sofortiger Neustart
- 20 Minuten nichts geladen → Neustart
- Netzwerkfehler → neuer Versuch mit wachsenden Abständen
- Nach 6 Stunden Dauerbetrieb → Neustart nachts um 03:30
- App aus der Übersicht entfernt → startet nach 2 Sekunden wieder

## APK bauen

Läuft automatisch über GitHub Actions, sobald sich etwas im Ordner `android/` ändert
(oder manuell: Reiter **Actions** → **Android-App bauen** → **Run workflow**).
Die fertige `signo.apk` liegt danach unter **Artifacts**.

Damit sich die APK installieren und später aktualisieren lässt, muss sie **immer mit
demselben Schlüssel** signiert sein. Dafür unter *Settings → Secrets and variables →
Actions* einmalig hinterlegen:

| Secret | Inhalt |
| --- | --- |
| `KEYSTORE_BASE64` | Keystore-Datei als Base64 (`base64 -w0 signo.jks`) |
| `KEYSTORE_PASSWORD` | Passwort des Keystores |
| `KEY_ALIAS` | Alias des Schlüssels |
| `KEY_PASSWORD` | Passwort des Schlüssels |

Neuen Schlüssel einmalig erzeugen (auf einem Rechner mit Java):

    keytool -genkeypair -v -keystore signo.jks -alias signo -keyalg RSA -keysize 2048 -validity 10000

Die Datei `signo.jks` und die Passwörter gut aufbewahren – ohne sie lassen sich
installierte Apps nicht mehr aktualisieren.

## Auf dem Server bereitstellen

`signo.apk` nach `storage/app/signo.apk` hochladen. Danach ist sie unter
`https://signo.at/app/signo.apk` erreichbar – ideal für die App „Downloader“ auf TV-Boxen.

## Installation auf dem Gerät

1. Auf der TV-Box „Installation aus unbekannten Quellen“ erlauben.
2. Mit der App „Downloader“ die Adresse `signo.at/app/signo.apk` öffnen und installieren.
3. App starten – es erscheint ein Code. Im Kundenbereich unter **Bildschirme** eingeben.
4. Einmalig die Frage nach der Akku-Optimierung mit „Zulassen“ bestätigen.

Optional kann die App als Startbildschirm (Home-App) gewählt werden – dann führt die
Home-Taste immer zurück zu Signo.
