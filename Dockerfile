FROM php:8.2-apache

# Habilitar mod_rewrite, permitir .htaccess y habilitar mysqli
RUN a2enmod rewrite \
	&& sed -ri "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf \
	&& docker-php-ext-install mysqli

COPY . /var/www/html/

# Establecer permisos apropiados
RUN chown -R www-data:www-data /var/www/html