# VPS Deployment Guide - Getwashed Loyalty

## Pre-Deployment Checklist

### 1. Environment Configuration
Update .env file with production values:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Use file cache for simple VPS, or redis for better performance
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Database optimization
DB_CONNECTION=mysql
```

### 2. Run Optimization Commands
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate optimized files
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 3. Database Optimization
```bash
# Run migrations
php artisan migrate --force

# Optimize database (run periodically)
php artisan db:optimize
```

### 4. Queue Worker (for WhatsApp broadcasts)
Setup supervisor for queue worker:
```ini
[program:getwashed-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/getwashed/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/getwashed/storage/logs/worker.log
stopwaitsecs=3600
```

### 5. Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/getwashed/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 6. Cron Job for Scheduled Tasks
```bash
* * * * * cd /var/www/getwashed && php artisan schedule:run >> /dev/null 2>&1
```

### 7. File Permissions
```bash
sudo chown -R www-data:www-data /var/www/getwashed
sudo chmod -R 755 /var/www/getwashed
sudo chmod -R 775 /var/www/getwashed/storage
sudo chmod -R 775 /var/www/getwashed/bootstrap/cache
```

## Performance Features Implemented

| Feature | Status | Description |
|---------|--------|-------------|
| Rate Limiting | Done | 120 req/min global, 5-10 req/min for auth |
| Database Indexes | Done | Optimized indexes on all lookup columns |
| Caching | Done | Dashboard stats cached for 5 minutes |
| DB Transactions | Done | Check-in uses transaction with rollback |
| Security Headers | Done | XSS, Clickjacking, MIME sniffing protection |
| Query Optimization | Done | Eager loading and select specific columns |
| Queue Jobs | Done | WhatsApp broadcasts run async |

## Monitoring

Check error logs:
```bash
tail -f /var/www/getwashed/storage/logs/laravel.log
```

Check queue status:
```bash
php artisan queue:monitor database:default
```
