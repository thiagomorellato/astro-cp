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

# Definir permissões (ajuste conforme sua necessidade)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Instalar dependências PHP via Composer
RUN composer install --no-dev --optimize-autoloader

# Rodar migrações (opcional - cuidado com isso em produção)
# RUN php artisan migrate --force

# Expor a porta que o Apache vai escutar
EXPOSE 80

# Rodar Apache em primeiro plano
CMD ["apache2-foreground"]
