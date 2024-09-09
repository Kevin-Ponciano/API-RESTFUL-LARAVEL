FROM php:8.2.9-fpm-bullseye

ARG APP_DIR=/var/www/app
ARG REDIS_LIB_VERSION=5.3.7

RUN apt-get update -y && apt-get install -y --no-install-recommends \
    apt-utils \
    supervisor

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libpq-dev \
    libxml2-dev \
    nano \
    cron \
    gnupg2 \
    wget \
    libpq-dev \
    lsb-release

RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql pgsql session xml

RUN pecl install redis-${REDIS_LIB_VERSION} \
    && docker-php-ext-enable redis

RUN docker-php-ext-install zip iconv simplexml pcntl gd fileinfo

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./docker/supervisord/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY ./docker/php/extra-php.ini "$PHP_INI_DIR/99_extra.ini"
COPY ./docker/php/extra-php-fpm.conf /etc/php8/php-fpm.d/www.conf

WORKDIR $APP_DIR
RUN cd $APP_DIR

RUN chown -R www-data:www-data $APP_DIR

COPY --chown=www-data:www-data ./ .

RUN chmod -R 777 bootstrap/cache

RUN rm -rf vendor
RUN composer install --no-interaction --no-dev --optimize-autoloader

RUN chmod -R 777 storage

RUN php artisan view:cache
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan event:cache

RUN echo "* * * * * cd $APP_DIR && php artisan schedule:run" > /etc/cron.d/laravel \
    && chmod 0644 /etc/cron.d/laravel \
    && crontab /etc/cron.d/laravel

RUN apt-get install nginx -y
RUN rm -rf /etc/nginx/sites-enabled/* && rm -rf /etc/nginx/sites-available/*
COPY ./docker/nginx/sites.conf /etc/nginx/sites-enabled/default.conf
COPY ./docker/nginx/error.html /var/www/html/error.html

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
