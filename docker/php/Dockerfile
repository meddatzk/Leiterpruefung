FROM php:8.2-fpm-alpine

# Installiere System-Dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    openldap-dev \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev

# Installiere PHP-Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        xml \
        zip \
        intl \
        mbstring \
        bcmath \
        opcache \
        ldap

# Installiere Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setze Arbeitsverzeichnis
WORKDIR /var/www/html

# Kopiere PHP-Konfiguration
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Erstelle www-data Benutzer
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Setze Berechtigungen
RUN chown -R www:www /var/www/html && \
    chmod -R 755 /var/www/html

# Wechsle zu www-data Benutzer
USER www

# Exponiere Port 9000
EXPOSE 9000

# Starte PHP-FPM
CMD ["php-fpm"]
