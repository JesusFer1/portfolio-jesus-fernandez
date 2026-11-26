FROM php:8.2-apache

# Instalar SQLite (para SkillHub y GymControl Pro) y Python 3
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    python3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

# Permisos de escritura para SQLite y carpetas de entregas/subidas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && if [ -d /var/www/html/api ]; then chmod -R 775 /var/www/html/api; fi \
    && if [ -d /var/www/html/proyectos/skillhub ]; then chmod -R 775 /var/www/html/proyectos/skillhub; fi \
    && if [ -d /var/www/html/proyectos/gimnasio ]; then chmod -R 775 /var/www/html/proyectos/gimnasio; fi

EXPOSE 10000

# Configurar dinámicamente el puerto que asigna Render al iniciar el contenedor
CMD ["/bin/sh", "-c", "printf 'Listen %s\\n' \"${PORT:-10000}\" > /etc/apache2/ports.conf && sed -ri \"s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${PORT:-10000}>/g\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
