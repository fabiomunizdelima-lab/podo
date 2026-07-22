#!/bin/sh
set -e

echo "Podo: avvio container ($1)…"

# Git: la working copy è montata da host (owner root) mentre giriamo come 'podo'.
# Senza questa eccezione git/composer segnalano "dubious ownership".
git config --global --add safe.directory /var/www/html 2>/dev/null || true

# Solo per il servizio principale php-fpm eseguiamo il setup una volta
if [ "$1" = "php-fpm" ]; then

    # La advisory-policy di Composer blocca in risoluzione le versioni con avvisi
    # di sicurezza. In ambiente non-lock la disattiviamo per poter installare.
    # (In produzione si committa un composer.lock verificato e la si lascia attiva.)
    composer config --global policy.advisories.block false 2>/dev/null || true

    if [ ! -d vendor ]; then
        echo "Installazione dipendenze PHP…"
        if [ -f composer.lock ]; then
            composer install --no-dev --optimize-autoloader --no-interaction
        else
            echo "composer.lock assente: risoluzione dipendenze (composer update)…"
            composer update --no-dev --optimize-autoloader --no-interaction
        fi
    fi

    if [ ! -d node_modules ]; then
        echo "Installazione e build asset frontend…"
        if [ -f package-lock.json ]; then
            npm ci
        else
            npm install
        fi
        npm run build
    fi

    # Genera APP_KEY se assente
    if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
        php artisan key:generate --force
    fi

    php artisan storage:link || true
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
