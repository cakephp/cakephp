ARG PHP_IMAGE_TAG=8.5-fpm-alpine
FROM php:${PHP_IMAGE_TAG}

LABEL org.opencontainers.image.source="https://github.com/cakephp/cakephp"

RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        gettext-dev \
        gmp-dev \
        icu-dev \
        libjpeg-turbo-dev \
        linux-headers \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libxslt-dev \
        libzip-dev \
        postgresql-dev; \
    apk add --no-cache \
        freetype \
        gmp \
        icu-libs \
        libintl \
        libjpeg-turbo \
        libpng \
        libpq \
        libwebp \
        libxml2 \
        libxslt \
        libzip; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j "$(nproc)" \
        bcmath exif gd gettext gmp intl mysqli pcntl \
        pdo_mysql pdo_pgsql pgsql soap sockets xsl zip; \
    apk del .build-deps; \
    php -r 'foreach (["bcmath", "exif", "gd", "gettext", "gmp", "intl", "mysqli", "pcntl", "pdo_mysql", "pdo_pgsql", "pgsql", "soap", "sockets", "xsl", "zip"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Missing PHP extension: $extension\n"); exit(1); } }'; \
    php-fpm -t
