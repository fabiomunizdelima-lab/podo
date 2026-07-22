#!/bin/sh
set -e

# Attende il database
echo "Podo: avvio container ($1)…"

# Solo per il servizio principale php-fpm eseguiamo setup una volta
if [ "$1" = "php-fpm" ]; then
    if [ ! -d vendor ]; then
        echo "Installazione dipendenze PHP…"
        composer install --no-dev --optimize-autoloader --no-interaction
    fi

    if [ ! -d node_modules ]; then
        echo "Installazione e build asset frontend…"
        npm ci && npm run build
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
