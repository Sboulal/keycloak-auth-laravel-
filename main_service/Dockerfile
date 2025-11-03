# 1. Image de base PHP avec FPM
FROM php:8.2-fpm-alpine

# 2. Installer les dépendances système
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    curl \
    mysql-client \
    postgresql-dev \
    icu-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip intl bcmath opcache

# 3. Installer Composer (copié depuis l'image officielle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4. Définir le répertoire de travail
WORKDIR /var/www

# 5. Copier le code du projet Laravel
COPY . .

# 6. Installer les dépendances PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# 7. Donner les permissions nécessaires à Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 8. Exposer le port utilisé par php artisan serve
EXPOSE 6500

# 9. Lancer le serveur Laravel
CMD php artisan serve --host=0.0.0.0 --port=6500
