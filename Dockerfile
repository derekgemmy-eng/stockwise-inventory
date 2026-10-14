FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html/
EXPOSE 80
