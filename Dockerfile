# Imagen base PHP + Apache
FROM php:8.2-apache

# Paquetes del sistema (zip, unzip, git, libs) + extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git libonig-dev libxml2-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring dom \
 && docker-php-ext-configure zip \
 && docker-php-ext-install zip \
 && a2enmod rewrite

# Directorio de la app
WORKDIR /var/www/html

# 1) Copiar composer.* e instalar dependencias (genera vendor/ dentro de la imagen)
COPY composer.json composer.lock ./

# Instalar Composer y dependencias del proyecto
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php \
 && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 2) Copiar el resto del código
COPY . .

# Puerto por defecto de Apache
EXPOSE 80

# Arrancar Apache
CMD ["apache2-foreground"]
