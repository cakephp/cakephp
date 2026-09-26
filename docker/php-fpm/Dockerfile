ARG PHP_IMAGE_TAG=8.5-fpm-bookworm
FROM php:${PHP_IMAGE_TAG}

LABEL org.opencontainers.image.source="https://github.com/cakephp/cakephp"

# The official php:*fpm-bookworm images are built from debian:bookworm-slim.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libgmp-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        libwebp-dev \
        libxml2-dev \
        libxslt1-dev \
        libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j "$(nproc)" \
        bcmath \
        exif \
        gd \
        gettext \
        gmp \
        intl \
        mysqli \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        soap \
        sockets \
        xsl \
        zip; \
    rm -rf /var/lib/apt/lists/*; \
    php -r 'foreach (["bcmath", "exif", "gd", "gettext", "gmp", "intl", "mysqli", "pcntl", "pdo_mysql", "pdo_pgsql", "pgsql", "soap", "sockets", "xsl", "zip"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Missing PHP extension: $extension\n"); exit(1); } }'; \
    php-fpm -t
