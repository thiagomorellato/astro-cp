FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl openssh-client autossh \
    && docker-php-ext-install zip pdo pdo_mysql bcmath

# Instalar Node.js e npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar código
COPY . /var/www/html

# Ajustes Apache
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 755 /var/www/html/public

WORKDIR /var/www/html

# PHP deps
RUN composer install --no-dev --optimize-autoloader

# Frontend build
RUN npm install && npm run build

# Copiar o script entrypoint
COPY entrypoint.sh /var/www/html/entrypoint.sh
RUN chmod +x /var/www/html/entrypoint.sh

EXPOSE 80

CMD ["/var/www/html/entrypoint.sh"]
