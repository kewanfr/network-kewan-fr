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

RUN a2enmod remoteip headers proxy

RUN printf "%s\n" \
  'RemoteIPHeader X-Forwarded-For' \
  'RemoteIPTrustedProxy 172.18.0.0/16' \
  'UseCanonicalName Off' \
  > /etc/apache2/conf-available/remoteip.conf \
  && a2enconf remoteip

  COPY vhost.conf /etc/apache2/sites-available/000-default.conf


RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# reverse proxy authorize
# RUN echo "ProxyPass / http://localhost:80/" >> /etc/apache2/apache2.conf

# RUN service apache2 restart

# Expose le port HTTP
EXPOSE 80
