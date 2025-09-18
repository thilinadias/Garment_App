FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql &&     a2enmod rewrite headers &&     echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && a2enconf servername
COPY docker/php/php.ini /usr/local/etc/php/conf.d/docker-php-custom.ini
WORKDIR /var/www/html
COPY app/ /var/www/html/
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html && chmod -R 775 /var/www/html/uploads
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=5 CMD wget -qO- http://localhost/ || exit 1
