#!/bin/sh
set -e

# Render trimite traficul pe portul din $PORT
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Certificatul CA al bazei de date (public, nu e secret) vine din repo, ca sa il poata citi si Apache
export MYSQL_ATTR_SSL_CA=/var/www/html/docker/db-ca.pem

php artisan migrate --force
# Categoriile si exercitiile de baza; ruleaza seederele doar cand fisierele lor s-au schimbat de la ultimul deploy
php artisan catalog:seed
php artisan config:cache
php artisan route:cache

exec apache2-foreground
