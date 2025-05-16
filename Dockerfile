# Use a imagem oficial PHP com Apache
FROM php:8.1-apache

# Instalar dependências do sistema, PHP e Composer
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install zip pdo pdo_mysql

# Instalar composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar o código para dentro do container
COPY . /var/www/html

# 🔧 Corrige o DocumentRoot do Apache para apontar para /public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# 🔥 Habilita mod_rewrite (necessário para Laravel)
RUN a2enmod rewrite

# 🔓 Permite o uso de .htaccess (Laravel usa isso)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Permissões para storage e cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Permissões para a pasta pública (previne erro 403)
RUN chmod -R 755 /var/www/html/public

# Instalar dependências PHP via Composer
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Expor a porta que o Apache vai escutar
EXPOSE 80

# Rodar Apache em primeiro plano
CMD ["apache2-foreground"]
