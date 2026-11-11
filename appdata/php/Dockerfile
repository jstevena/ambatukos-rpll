FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

COPY apache.conf /etc/apache2/conf-available/ambatukos.conf
COPY php.ini /usr/local/etc/php/conf.d/ambatukos.ini
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && a2enconf ambatukos \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
