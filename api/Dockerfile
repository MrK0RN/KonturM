FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

# --no-scripts: post-install вызывает cache:clear до появления bin/console (COPY ниже)
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .

RUN composer dump-autoload --optimize --no-interaction \
    && (php bin/console assets:install public --no-interaction || true) \
    && (php bin/console cache:clear --no-warmup --no-interaction || true)

RUN mkdir -p var/cache var/log config/jwt \
    && chmod -R 777 var

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/router.php"]
