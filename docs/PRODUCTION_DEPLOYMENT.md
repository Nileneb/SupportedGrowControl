# Production Deployment - Checkliste

## VOR dem Build auf dem Production Server

### 1. Repository auf Production Server klonen/pullen

```bash
# Falls noch nicht geklont
git clone https://github.com/Nileneb/growdash.git
cd growdash

# Falls bereits vorhanden
git pull origin main
```

### 2. Environment-Datei konfigurieren

```bash
# .env aus Beispiel kopieren (falls nicht vorhanden)
cp .env.example .env

# .env bearbeiten für Production
nano .env
```

**Wichtige .env-Einstellungen für Production:**

```dotenv
APP_NAME=GrowDash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# App Key generieren (falls neu)
# php artisan key:generate

# Database (MySQL/PostgreSQL statt SQLite)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=growdash_production
DB_USERNAME=growdash_user
DB_PASSWORD=secure_password_here

# Broadcasting für WebSockets
BROADCAST_CONNECTION=reverb

# Reverb WebSocket (HTTPS/WSS für Production!)
REVERB_APP_ID=683260
REVERB_APP_KEY=zkzj14faofpwi4hhad9w
REVERB_APP_SECRET=kw7lnemcht7nnoxcntta
REVERB_HOST="yourdomain.com"
REVERB_PORT=443
REVERB_SCHEME=https

# Vite Frontend
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_STORE=database

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Mail (für Benachrichtigungen)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 3. PHP Dependencies installieren

```bash
composer install --optimize-autoloader --no-dev
```

**Flags erklärt:**

-   `--optimize-autoloader`: Erstellt optimierte Autoload-Klassen
-   `--no-dev`: Installiert keine Development-Dependencies

### 4. Node.js Dependencies installieren

```bash
npm ci
```

**Wichtig:** `npm ci` statt `npm install` - ist deterministisch und nutzt `package-lock.json`

---

## BUILD auf Production Server ausführen

### Frontend Build

```bash
npm run build
```

**Was passiert:**

-   Vite kompiliert `resources/js/app.js` + `resources/css/app.css`
-   Output: `public/build/` mit optimierten Assets
-   Manifest wird erstellt für Asset-Versioning

**Erwartete Ausgabe:**

```
vite v7.0.6 building for production...
✓ 42 modules transformed.
public/build/assets/app-a1b2c3d4.js    125.45 kB │ gzip: 42.31 kB
public/build/assets/app-e5f6g7h8.css   89.23 kB │ gzip: 18.92 kB
✓ built in 3.42s
```

---

## NACH dem Build

### 1. Datenbank Setup

```bash
# Datenbank migrieren
php artisan migrate --force

# Optional: Seeder für Test-Daten (NUR für Staging!)
# php artisan db:seed
```

**`--force` Flag:** Erforderlich in Production, da Laravel sonst fragt

### 2. Laravel Optimierungen

```bash
# Cache Config
php artisan config:cache

# Cache Routes
php artisan route:cache

# Cache Views
php artisan view:cache

# Cache Events
php artisan event:cache
```

**Was wird gecacht:**

-   `bootstrap/cache/config.php` - Alle Configs
-   `bootstrap/cache/routes-v7.php` - Alle Routes
-   `storage/framework/views/` - Compilierte Blade-Templates

### 3. Storage Link erstellen

```bash
php artisan storage:link
```

**Erstellt Symlink:** `public/storage` → `storage/app/public`

### 4. Permissions setzen

```bash
# Storage & Cache Ordner beschreibbar machen
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Optional: Gesamte App
chown -R www-data:www-data /var/www/growdash
```

### 5. Supervisor für Background-Prozesse

**Reverb WebSocket Server:**

```bash
sudo nano /etc/supervisor/conf.d/growdash-reverb.conf
```

```ini
[program:growdash-reverb]
process_name=%(program_name)s
command=php /var/www/growdash/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/growdash/storage/logs/reverb.log
stopwaitsecs=3600
```

**Queue Worker:**

```bash
sudo nano /etc/supervisor/conf.d/growdash-queue.conf
```

```ini
[program:growdash-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/growdash/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/growdash/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Supervisor neu laden:**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start growdash-reverb:*
sudo supervisorctl start growdash-queue:*

# Status prüfen
sudo supervisorctl status
```

### 6. Nginx/Apache Konfiguration

**Nginx Beispiel:**

```bash
sudo nano /etc/nginx/sites-available/growdash
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/growdash/public;
    index index.php index.html;

    # SSL Certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/growdash_access.log;
    error_log /var/log/nginx/growdash_error.log;

    # PHP-FPM
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # WebSocket Proxy (Reverb auf Port 8080)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Static Assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

**Site aktivieren:**

```bash
sudo ln -s /etc/nginx/sites-available/growdash /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. SSL Zertifikat (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 8. Firewall (UFW)

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw allow 8080/tcp  # Reverb WebSocket (intern)
sudo ufw enable
```

---

## Deployment Workflow (bei Updates)

### Schneller Deploy mit Script

**deploy.sh erstellen:**

```bash
nano deploy.sh
chmod +x deploy.sh
```

```bash
#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# 1. Git Pull
echo "📥 Pulling latest code..."
git pull origin main

# 2. Dependencies
echo "📦 Installing dependencies..."
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# 3. Maintenance Mode ON
echo "🔧 Enabling maintenance mode..."
php artisan down

# 4. Database Migrations
echo "💾 Running migrations..."
php artisan migrate --force

# 5. Clear & Cache
echo "🗑️  Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✨ Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Restart Services
echo "🔄 Restarting services..."
sudo supervisorctl restart growdash-reverb:*
sudo supervisorctl restart growdash-queue:*

# 7. Maintenance Mode OFF
echo "✅ Disabling maintenance mode..."
php artisan up

echo "🎉 Deployment completed successfully!"
```

**Deploy ausführen:**

```bash
./deploy.sh
```

---

## Monitoring & Logs

### Logs überwachen

```bash
# Laravel Logs
tail -f storage/logs/laravel.log

# Reverb Logs
tail -f storage/logs/reverb.log

# Queue Worker Logs
tail -f storage/logs/queue-worker.log

# Nginx Access Logs
tail -f /var/log/nginx/growdash_access.log

# Nginx Error Logs
tail -f /var/log/nginx/growdash_error.log
```

### Log Rotation einrichten

```bash
sudo nano /etc/logrotate.d/growdash
```

```
/var/www/growdash/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    create 0644 www-data www-data
}
```

---

## Health Checks

### 1. App erreichbar?

```bash
curl -I https://yourdomain.com
# Erwartung: HTTP/2 200
```

### 2. WebSocket funktioniert?

```bash
# Browser Console
window.Echo.connector.pusher.connection.state
// Erwartung: "connected"
```

### 3. Queue Worker läuft?

```bash
sudo supervisorctl status growdash-queue:*
# Erwartung: RUNNING
```

### 4. Reverb läuft?

```bash
sudo supervisorctl status growdash-reverb:*
# Erwartung: RUNNING

# Port Check
netstat -tuln | grep 8080
# Erwartung: LISTEN auf 0.0.0.0:8080
```

---

## Troubleshooting Production

### 500 Error - Check Laravel Logs

```bash
tail -n 50 storage/logs/laravel.log
```

### WebSocket nicht verbunden

**Check 1: Reverb läuft?**

```bash
sudo supervisorctl status growdash-reverb:*
```

**Check 2: Nginx Proxy korrekt?**

```bash
curl -I http://127.0.0.1:8080/app/zkzj14faofpwi4hhad9w
```

**Check 3: Firewall offen?**

```bash
sudo ufw status | grep 8080
```

### Permissions Probleme

```bash
# Storage nicht beschreibbar
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Cache-Probleme nach Update

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

---

## Backup Strategy

### 1. Datenbank Backup

```bash
# MySQL Dump
mysqldump -u growdash_user -p growdash_production > backup_$(date +%Y%m%d).sql

# Automatisiert mit Cron
crontab -e
# Täglich um 2 Uhr
0 2 * * * mysqldump -u growdash_user -p'password' growdash_production > /backups/growdash_$(date +\%Y\%m\%d).sql
```

### 2. Files Backup

```bash
# Gesamte App
tar -czf growdash_backup_$(date +%Y%m%d).tar.gz /var/www/growdash

# Nur Storage
tar -czf storage_backup_$(date +%Y%m%d).tar.gz /var/www/growdash/storage
```

---

## Performance Optimierungen

### 1. OPcache aktivieren

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

### 2. Redis für Cache/Sessions (Optional)

```dotenv
# .env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Zusammenfassung: Production Checklist

**VOR dem Build:**

-   ✅ Git pull/clone
-   ✅ .env konfigurieren (Production-Werte!)
-   ✅ `composer install --optimize-autoloader --no-dev`
-   ✅ `npm ci`

**Build:**

-   ✅ `npm run build`

**NACH dem Build:**

-   ✅ `php artisan migrate --force`
-   ✅ `php artisan config:cache`
-   ✅ `php artisan route:cache`
-   ✅ `php artisan view:cache`
-   ✅ `php artisan storage:link`
-   ✅ Permissions: `chmod -R 775 storage bootstrap/cache`
-   ✅ Supervisor: Reverb + Queue Worker
-   ✅ Nginx/Apache konfigurieren
-   ✅ SSL Zertifikat (Let's Encrypt)
-   ✅ Firewall (UFW)

**Bei jedem Update:**

-   ✅ `./deploy.sh` ausführen
-   ✅ Logs prüfen
-   ✅ Health Checks

🚀 **Production ist bereit!**
