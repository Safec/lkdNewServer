FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    zlib1g-dev \
    libonig-dev \
    libxslt1-dev \
    libpq-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libmemcached-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        intl \
        gd \
        opcache \
        pdo \
        pdo_mysql \
        zip \
        bcmath \
        ctype \
        xml \
        mbstring \
        xsl \
        sockets \
        pcntl \
        pdo_pgsql \
    && pecl channel-update pecl.php.net \
    && pecl install redis \
    && pecl install memcached \
    && docker-php-ext-enable redis memcached

# Установка Composer (более надёжный способ)
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php

COPY ./php.ini /usr/local/etc/php/conf.d/php-custom.ini

WORKDIR /var/www/html

CMD ["php-fpm"]
