# Utilise PHP 8.1 avec Apache
FROM php:8.1-apache

# Installe les extensions PDO pour MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Active la réécriture d’URL Apache (facultatif, utile pour les frameworks)
RUN a2enmod rewrite

# Copie le code source dans le conteneur
COPY src/ /var/www/html/

# Fixe les droits si besoin
RUN chown -R www-data:www-data /var/www/html

RUN echo "ServerName network.kewan.fr" >> /etc/apache2/apache2.conf

RUN service apache2 restart

# Expose le port HTTP
EXPOSE 80
