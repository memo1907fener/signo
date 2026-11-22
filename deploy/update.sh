#!/usr/bin/env bash
# Signo – Update vom GitHub-Repository holen und einspielen.
# Im Projektordner ausführen:  bash deploy/update.sh
set -e
cd "$(dirname "$0")/.."
PHP_BIN="${PHP_BIN:-$(command -v php84 || command -v php)}"
COMPOSER="composer"; command -v composer >/dev/null 2>&1 || COMPOSER="$PHP_BIN composer.phar"

$PHP_BIN artisan down --retry=15 || true
git pull --ff-only
COMPOSER_ALLOW_SUPERUSER=1 $COMPOSER install --no-dev --optimize-autoloader --no-interaction
$PHP_BIN artisan migrate --force
[ -e public/storage ] || ln -s ../storage/app/public public/storage
$PHP_BIN artisan optimize:clear >/dev/null
$PHP_BIN artisan optimize
if id www >/dev/null 2>&1; then chown -R www:www storage bootstrap/cache; fi
$PHP_BIN artisan up
echo "Update fertig: $(git log -1 --format='%h %s')"
