#!/bin/sh
mkdir -p /var/www/storage/logs /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
exec "$@"
