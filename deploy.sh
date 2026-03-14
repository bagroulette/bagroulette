#!/bin/bash
# =============================================================================
# BagRoulette VPS Setup Script
# Ubuntu 22.04 LTS — Run as root or with sudo
# Usage: bash deploy.sh
# =============================================================================

set -e
echo "🎰 BagRoulette VPS Setup Starting..."

DOMAIN="api.bagroulette.xyz"
PHP_VERSION="8.2"
APP_DIR="/var/www/bagroulette"
DB_NAME="bagroulette"
DB_USER="bagroulette"
DB_PASS=$(openssl rand -base64 24)

# ─── 1. System packages ────────────────────────────────────────────────────────
apt update && apt upgrade -y
apt install -y curl wget git unzip nginx certbot python3-certbot-nginx \
    software-properties-common supervisor redis-server

# ─── 2. PHP 8.2 ───────────────────────────────────────────────────────────────
add-apt-repository ppa:ondrej/php -y
apt install -y php${PHP_VERSION}-fpm php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql php${PHP_VERSION}-redis php${PHP_VERSION}-curl \
    php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath php${PHP_VERSION}-gd php${PHP_VERSION}-intl

# ─── 3. Composer ──────────────────────────────────────────────────────────────
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# ─── 4. MySQL ─────────────────────────────────────────────────────────────────
apt install -y mysql-server
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
echo "✅ MySQL: DB=${DB_NAME} PASS=${DB_PASS}"

# ─── 5. Deploy Laravel app ────────────────────────────────────────────────────
mkdir -p ${APP_DIR}
cd ${APP_DIR}

if [ -d ".git" ]; then
    git pull origin main
else
    git clone https://github.com/YOUR_USERNAME/bagroulette-backend.git .
fi

composer install --optimize-autoloader --no-dev

# Create .env if not exists
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
    cat >> .env << EOF

# ─── BagRoulette Specific ───
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BAGS_API_KEY=REPLACE_ME
BAGS_TOKEN_MINT=REPLACE_ME
BAGS_TREASURY_WALLET=REPLACE_ME
BAGS_TREASURY_KEYPAIR=REPLACE_ME
HELIUS_API_KEY=REPLACE_ME

BROADCAST_DRIVER=reverb
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REVERB_APP_ID=bagroulette
REVERB_APP_KEY=bagroulette_key
REVERB_APP_SECRET=REPLACE_ME
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
EOF
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache

chown -R www-data:www-data ${APP_DIR}
chmod -R 755 ${APP_DIR}/storage

# ─── 6. Nginx config ──────────────────────────────────────────────────────────
cat > /etc/nginx/sites-available/bagroulette << EOF
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;

    # ── Laravel API ──
    location /api {
        try_files \$uri \$uri/ /index.php?\$query_string;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # ── WebSocket (Reverb) → proxy to port 8080 ──
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_read_timeout 3600;
        proxy_send_timeout 3600;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht { deny all; }

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
}
EOF

ln -sf /etc/nginx/sites-available/bagroulette /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# ─── 7. SSL via Let's Encrypt ─────────────────────────────────────────────────
certbot --nginx -d ${DOMAIN} --non-interactive --agree-tos -m admin@bagroulette.xyz || \
    echo "⚠️  SSL setup failed — run manually: certbot --nginx -d ${DOMAIN}"

# ─── 8. Supervisor: Queue worker + Reverb WebSocket ──────────────────────────
cat > /etc/supervisor/conf.d/bagroulette.conf << EOF
[program:bagroulette-queue]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600

[program:bagroulette-reverb]
process_name=%(program_name)s
command=php ${APP_DIR}/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/reverb.log
EOF

supervisorctl reread
supervisorctl update
supervisorctl start all

# ─── 9. Cron for Laravel scheduler ───────────────────────────────────────────
(crontab -l 2>/dev/null; echo "* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# ─── 10. Redis config ─────────────────────────────────────────────────────────
sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
systemctl restart redis-server

echo ""
echo "═══════════════════════════════════════════════════════"
echo "✅  BagRoulette API deployed!"
echo "   Domain  : https://${DOMAIN}"
echo "   DB Pass : ${DB_PASS}   ← SAVE THIS!"
echo "   Next    : Update .env with your API keys at ${APP_DIR}/.env"
echo "═══════════════════════════════════════════════════════"
