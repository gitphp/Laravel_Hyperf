#!/bin/sh
set -e

# Create Symfony scratch project if directory is empty / no composer.json
if [ ! -f composer.json ]; then
  echo "No composer.json found. Creating new Laravel skeleton project..."
  composer create-project laravel/laravel:"^13.0" skeleton_project --no-interaction
  mv skeleton_project/* /var/www/html
  mv skeleton_project/.[!.]* /var/www/html
  rmdir skeleton_project
fi

# Install dependencies (idempotent with file sharing)
if [ -f composer.json ]; then
  composer install --no-interaction --prefer-dist
fi

# So PHP-FPM (www-data) can read mounted app files
chown -R www-data:www-data /var/www/html 2>/dev/null || true

# Run the main command
exec "$@"
