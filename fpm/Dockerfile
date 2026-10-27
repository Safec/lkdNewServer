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
    && git clone --depth 1 --branch 6.0.2 https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-ext-enable redis \
    && cd / \
    && rm -rf /tmp/phpredis \
   && git clone --depth 1 --branch v3.2.0 https://github.com/php-memcached-dev/php-memcached.git /tmp/php-memcached \
    && cd /tmp/php-memcached \
    && phpize \
    && ./configure --disable-memcached-sasl \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-ext-enable memcached \
    && cd / \
    && rm -rf /tmp/php-memcached

# Установка Composer (более надёжный способ)
RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php

COPY ./php.ini /usr/local/etc/php/conf.d/php-custom.ini

WORKDIR /var/www/html

CMD ["php-fpm"]
