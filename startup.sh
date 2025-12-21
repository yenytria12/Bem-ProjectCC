#!/bin/bash

# Copy nginx config
cp /home/site/wwwroot/default /etc/nginx/sites-available/default

# Run Laravel optimizations
cd /home/site/wwwroot
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart nginx
service nginx reload
