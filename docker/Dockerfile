FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Enable headers module
RUN apt-get update && apt-get install -y apache2 && a2enmod headers

#COPY src /var/www/public
#RUN chown -R www-data:www-data /var/www/public

CMD ["apache2-foreground"]

