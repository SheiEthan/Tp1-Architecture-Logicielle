FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Activer mod_rewrite pour Apache
RUN a2enmod rewrite

# Copier la configuration Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du service
COPY . /var/www/html/

# Changer les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposer le port 80
EXPOSE 80
