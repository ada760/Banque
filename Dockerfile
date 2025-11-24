# Étape 1: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

COPY . .

RUN composer install --no-scripts --optimize-autoloader --no-interaction --prefer-dist --ignore-platform-req=ext-mongodb --ignore-platform-req=ext-gd

# Étape 2: Image finale
FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Installer l'extension MongoDB
RUN apk add --no-cache autoconf g++ make \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apk del autoconf g++ make

RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

WORKDIR /var/www/html
COPY --from=composer-build /app /var/www/html

# Copier et rendre exécutable le script de démarrage
COPY render-startup.sh /var/www/html/render-startup.sh
RUN chmod +x /var/www/html/render-startup.sh && ls -la /var/www/html/render-startup.sh

RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 8000

# Commande de démarrage simplifiée
CMD ["sh", "-c", "\
    echo '🚀 Démarrage Laravel...' && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan migrate --force && \
    echo '✅ Application prête' && \
    php artisan serve --host=0.0.0.0 --port=8000 \
"]