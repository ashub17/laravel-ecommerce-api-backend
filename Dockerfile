# Laravel API image for Render.
#
# Render has no native PHP runtime, so this is a Docker service. nginx and
# PHP-FPM run side by side under supervisord in one container — Render's free
# tier gives one process group, so splitting them into separate services is not
# an option.

FROM php:8.3-fpm-alpine

# --- system packages -------------------------------------------------------
# `gettext` supplies envsubst, used at boot to inject Render's $PORT into the
# nginx config. Build deps are removed again in the same layer.
RUN apk add --no-cache \
        nginx \
        supervisor \
        gettext \
        icu-libs \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        zip \
        opcache \
    && apk del .build-deps

# --- PHP configuration -----------------------------------------------------
# Opcache is the single largest performance win for a container that never
# changes its code after build.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'expose_php=0'; \
        echo 'memory_limit=256M'; \
        echo 'upload_max_filesize=8M'; \
        echo 'post_max_size=10M'; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- dependencies ----------------------------------------------------------
# Copied before the rest of the app so this layer is cached until the
# dependencies themselves change.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Render injects PORT; 8080 is only the local default.
ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
