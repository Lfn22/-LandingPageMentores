FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && printf 'PassEnv APP_CONFIG_FILE\n' > /etc/apache2/conf-available/app-env.conf \
    && a2enconf app-env
