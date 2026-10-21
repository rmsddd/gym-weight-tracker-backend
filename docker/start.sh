#!/bin/sh
set -e

# Render trimite traficul pe portul din $PORT
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Certificatul CA al bazei de date (public, nu e secret) vine din repo, ca sa il poata citi si Apache
export MYSQL_ATTR_SSL_CA=/var/www/html/docker/db-ca.pem

# Baza de date poate raspunde greu imediat dupa pornire, asa ca reincearca un minut inainte sa renunte
attempt=1
until php artisan migrate --force; do
    if [ "$attempt" -ge 10 ]; then
        echo "Baza de date nu raspunde dupa $attempt incercari, opresc containerul" >&2
        exit 1
    fi
    echo "Migrarea a esuat (incercarea $attempt/10), reincerc in 6 secunde..." >&2
    attempt=$((attempt + 1))
    sleep 6
done
# Permisiunile din cod si rolurile root si sportiv; ruleaza la fiecare deploy, e rapid
php artisan permissions:sync
# Contul root, cu parola din ROOT_PASSWORD; il creeaza doar daca nu exista, parola nu o schimba
php artisan root:ensure
# Categoriile si exercitiile de baza; ruleaza seederele doar cand fisierele lor s-au schimbat de la ultimul deploy
php artisan catalog:seed
php artisan config:cache
php artisan route:cache

exec apache2-foreground
