#!/bin/sh
set -e

# Render trimite traficul pe portul din $PORT
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan migrate --force
# Categoriile si exercitiile de baza; seederele folosesc firstOrCreate, deci pot rula la fiecare pornire
php artisan db:seed --class=CategorySeeder --force
php artisan db:seed --class=ExerciseSeeder --force
php artisan config:cache
php artisan route:cache

exec apache2-foreground
