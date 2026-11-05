FROM php:8.4-cli
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libsqlite3-dev libzip-dev \
 && docker-php-ext-install pdo_sqlite zip \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Límites de subida acordes al máximo de 2 MB de la API (post_max_size deja margen para el resto del formulario).
RUN printf 'upload_max_filesize=2M\npost_max_size=4M\n' > /usr/local/etc/php/conf.d/uploads.ini
WORKDIR /var/www
