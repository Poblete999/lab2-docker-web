FROM php:8.2-apache

# Instalar la extensión PDO para conectarse a MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite (útil si después queremos URLs limpias)
RUN a2enmod rewrite

EXPOSE 80
