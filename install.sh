#!/bin/bash

echo "================> Installing Composer dependencies"
composer install --optimize-autoloader --no-dev

echo "================> Generate app key"
php artisan key:generate

echo "================> Optimizing Laravel"
php artisan optimize:clear
php artisan optimize

echo "================> Set folder permissions"
chmod -R o+w storage/app
chmod -R o+w storage/framework/cache
chmod -R o+w storage/framework/sessions
chmod -R o+w storage/framework/views
chmod -R o+w storage/logs
chmod -R o+w storage
chmod -R o+w bootstrap/cache

echo "================> Migrate database"
php artisan migrate --seed

echo "================> Installation completed!"
