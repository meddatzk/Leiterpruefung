FROM php:8.1-apache

# Installiere System-Dependencies
RUN apt-get update && apt-get install -y \
    libldap2-dev \
    libssl-dev \
    libsasl2-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Installiere PHP Extensions
RUN docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install \
    pdo_mysql \
    ldap \
    zip

# Aktiviere Apache Module
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod ssl

# Setze PHP Konfiguration
RUN echo "date.timezone = \${PHP_TIMEZONE}" > /usr/local/etc/php/conf.d/timezone.ini \
    && echo "memory_limit = \${PHP_MEMORY_LIMIT}" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" > /usr/local/etc/php/conf.d/execution.ini

# Setze Arbeitsverzeichnis
WORKDIR /var/www/html

# Setze Berechtigungen
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponiere Port 80
EXPOSE 80

# Starte Apache im Vordergrund
CMD ["apache2-foreground"]
