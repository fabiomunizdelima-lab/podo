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

# NOTA sicurezza (dev/test): in questo setup con codice montato da bind-mount
# (di proprietà di root sulla CT) i processi girano come root per evitare
# problemi di permessi di scrittura su vendor/, node_modules/, storage/.
# In PRODUZIONE il codice va copiato nell'immagine in fase di build e i
# processi eseguiti come utente non privilegiato (www-data). Vedi docs/SICUREZZA.md.
RUN sed -i 's/^user = www-data/user = root/; s/^group = www-data/group = root/' \
        /usr/local/etc/php-fpm.d/www.conf

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
