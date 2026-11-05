FROM php:8.4-cli
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libsqlite3-dev libzip-dev \
 && docker-php-ext-install pdo_sqlite zip \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Límites de subida acordes al máximo de 10 MB de la API (por defecto PHP permite solo 2 MB).
RUN printf 'upload_max_filesize=10M\npost_max_size=12M\n' > /usr/local/etc/php/conf.d/uploads.ini
WORKDIR /var/www
