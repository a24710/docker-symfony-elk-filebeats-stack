FROM php:8-fpm
RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer 
RUN chmod +x /usr/bin/composer
RUN composer self-update
RUN apt-get update 
RUN apt-get install -y git acl openssl openssh-client wget zip librabbitmq-dev libssh-dev libpq-dev
RUN apt-get install -y libpng-dev zlib1g-dev libzip-dev libxml2-dev libicu-dev
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-install pdo pdo_mysql
COPY ./xdebug-linux.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
WORKDIR /var/www/html











