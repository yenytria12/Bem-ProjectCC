#!/bin/bash

# ===========================================
# Laravel Deployment Script for Azure
# ===========================================

set -e

echo "🚀 Starting deployment..."

# Navigate to app directory
cd /home/site/wwwroot

# Install/update composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Clear and cache config
echo "⚙️ Caching configuration..."
php artisan config:clear
php artisan config:cache

# Clear and cache routes
echo "🛣️ Caching routes..."
php artisan route:clear
php artisan route:cache

# Clear and cache views
echo "👁️ Caching views..."
php artisan view:clear
php artisan view:cache

# Run database migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Clear application cache
echo "🧹 Clearing application cache..."
php artisan cache:clear

# Optimize
echo "⚡ Optimizing..."
php artisan optimize

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ Deployment completed successfully!"
