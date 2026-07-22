# ============================================================
#  Podo - Immagine PHP-FPM per Debian/Cloud
#  PHP 8.3 con estensioni per PostgreSQL, Redis, Argon2, GD
# ============================================================
FROM php:8.3-fpm-bookworm

# Pacchetti di sistema e librerie
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libicu-dev libargon2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql zip gd intl bcmath opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node.js 20 per il build degli asset (Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurazione PHP (opcache, limiti, sicurezza)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/podo.ini

WORKDIR /var/www/html

# Il master php-fpm gira come root (default) e i worker del pool come www-data
# (php-fpm RIFIUTA di eseguire il pool come root). L'entrypoint, eseguito da root,
# esegue composer/npm/migrate e poi assegna a www-data i permessi di scrittura
# su storage/ e bootstrap/cache/ (necessario col codice montato da bind-mount).
# In PRODUZIONE: copiare il codice nell'immagine e usare ownership www-data.

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
