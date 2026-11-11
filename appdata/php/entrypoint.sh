#!/bin/sh
set -e
if [ "$1" = "apache2-foreground" ]; then
  mkdir -p /var/www/html/storage/bukti /var/www/html/public/uploads/kamar
  chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads
  php /var/www/html/scripts/seed.php || echo "[seed] dilewati / gagal"
fi
exec "$@"
