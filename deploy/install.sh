#!/usr/bin/env bash
# Signo – Erstinstallation auf dem Server (aaPanel).
# Im Projektordner ausführen:  bash deploy/install.sh
set -e
cd "$(dirname "$0")/.."
APP_DIR="$(pwd)"
PHP_BIN="${PHP_BIN:-$(command -v php84 || command -v php)}"

echo "==> Projektordner: $APP_DIR"
echo "==> PHP: $($PHP_BIN -r 'echo PHP_VERSION;')"

# Composer besorgen, falls nicht vorhanden
if command -v composer >/dev/null 2>&1; then
    COMPOSER="composer"
else
    echo "==> Lade Composer …"
    $PHP_BIN -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $PHP_BIN composer-setup.php --quiet && rm composer-setup.php
    COMPOSER="$PHP_BIN composer.phar"
fi

if [ ! -f .env ]; then
    cp .env.example .env
    echo ""
    echo "!! Die Datei .env wurde angelegt."
    echo "!! Bitte jetzt öffnen und mindestens DB_DATABASE, DB_USERNAME, DB_PASSWORD"
    echo "!! sowie die E-Mail-Einstellungen (MAIL_…) eintragen. Danach dieses Skript erneut starten."
    exit 0
fi

echo "==> Installiere PHP-Pakete …"
COMPOSER_ALLOW_SUPERUSER=1 $COMPOSER install --no-dev --optimize-autoloader --no-interaction

if ! grep -q '^APP_KEY=base64' .env; then
    $PHP_BIN artisan key:generate --force
fi

echo "==> Datenbank …"
$PHP_BIN artisan migrate --force

echo "==> Öffentlicher Speicher …"
mkdir -p storage/app/public
[ -e public/storage ] || ln -s ../storage/app/public public/storage

echo "==> Zwischenspeicher …"
$PHP_BIN artisan optimize:clear >/dev/null
$PHP_BIN artisan optimize

# Rechte für den Webserver (aaPanel: Benutzer www)
if id www >/dev/null 2>&1; then
    chown -R www:www storage bootstrap/cache
fi
chmod -R ug+rwX storage bootstrap/cache

echo ""
echo "FERTIG. Signo läuft unter: $(grep '^APP_URL=' .env | cut -d= -f2)"
echo "Nicht vergessen: Cronjob für '$PHP_BIN $APP_DIR/artisan schedule:run' jede Minute."
