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

# Copier le script de démarrage et le rendre exécutable
COPY render-startup.sh /var/www/html/render-startup.sh
RUN chmod +x /var/www/html/render-startup.sh

RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 8000

# Utiliser le script de démarrage comme commande
CMD ["/var/www/html/render-startup.sh"]