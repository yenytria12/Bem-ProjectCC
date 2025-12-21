#!/bin/sh

echo ">>> MEMULAI STARTUP SCRIPT..."

# 1. Pastikan folder public ada
if [ -d "/home/site/wwwroot/public" ]; then
    echo ">>> Folder public ditemukan."
else
    echo ">>> WARNING: Folder public TIDAK ditemukan!"
fi

# 2. Benerin Nginx Root & Routing
echo ">>> Memperbaiki konfigurasi Nginx..."
cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.bak
sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' /etc/nginx/sites-available/default
sed -i 's|index  index.php index.html index.htm hostingstart.html;|index  index.php index.html index.htm hostingstart.html; try_files $uri $uri/ /index.php?$query_string;|g' /etc/nginx/sites-available/default

# 3. Reload Nginx
echo ">>> Reloading Nginx..."
service nginx reload

# 4. Jalanin Migrasi Otomatis (Opsional tapi berguna)
# cd /home/site/wwwroot && php artisan migrate --force

# 5. Start PHP
echo ">>> Starting PHP-FPM..."
php-fpm