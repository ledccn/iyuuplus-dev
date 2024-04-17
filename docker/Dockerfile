FROM php:8.3-zts-alpine

COPY . /iyuu
WORKDIR /iyuu

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions pcntl gd xdebug curl fileinfo mbstring mysqli openssl pdo_mysql pdo_sqlite sqlite3 sockets sodium pcntl zip \
    && wget https://getcomposer.org/installer -O - -q | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install

CMD ["php", "start.php", "start"]
