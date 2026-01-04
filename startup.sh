#!/bin/bash
cd /home/site/wwwroot

# Run migrations with seeding
php artisan migrate --seed --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if not exists
php artisan storage:link 2>/dev/null || true
