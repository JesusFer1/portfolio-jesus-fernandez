FROM php:8.2-apache

# Instalar SQLite (para SkillHub y GymControl Pro) y Python 3
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    python3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Configurar el puerto dinámico de Render (por defecto 80)
RUN sed -i 's/Listen 80/Listen ${PORT:-80}/g' /etc/apache2/ports.conf \
    && sed -i 's/:80/:${PORT:-80}/g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html/

# Permisos de escritura para SQLite y carpetas de entregas/subidas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && if [ -d /var/www/html/proyectos/skillhub ]; then chmod -R 775 /var/www/html/proyectos/skillhub; fi \
    && if [ -d /var/www/html/proyectos/gimnasio ]; then chmod -R 775 /var/www/html/proyectos/gimnasio; fi

EXPOSE 80
CMD ["apache2-foreground"]
