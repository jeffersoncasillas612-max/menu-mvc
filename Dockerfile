# Imagen base de PHP con Apache
FROM php:8.2-apache

# Instala extensiones necesarias para conectar con MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia tu código al directorio público de Apache
COPY . /var/www/html/

# Permite .htaccess (si usas)
RUN a2enmod rewrite
