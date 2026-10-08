#!/bin/bash
# =============================================================================
# Automated VPS Initial Server Setup Script
# For Next.js (Frontend) + Laravel (Backend) + PM2 + Nginx + PHP 8.4
# =============================================================================
set -e

if [ "$EUID" -ne 0 ]; then
  echo "Please run as root (or use sudo)"
  exit 1
fi

echo "=========================================================="
echo "  Starting Server Setup (Next.js + Laravel + Nginx + PM2) "
echo "=========================================================="

# 1. Update and Upgrade System
echo "[1/8] Updating system packages..."
apt-get update -y && apt-get upgrade -y
apt-get install -y curl wget git unzip build-essential ufw ufw-doc software-properties-common

# 2. Install PHP 8.4 and required extensions
echo "[2/8] Installing PHP 8.4 and extensions..."
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y php8.4 php8.4-cli php8.4-fpm php8.4-mbstring php8.4-xml php8.4-curl php8.4-sqlite3 php8.4-mysql php8.4-zip php8.4-bcmath php8.4-intl

# 3. Install Composer
echo "[3/8] Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 4. Install Node.js 20.x (LTS) & PM2
echo "[4/8] Installing Node.js 20.x & PM2..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs
npm install -g pm2
pm2 startup systemd -u root --hp /root

# 5. Install Nginx & Certbot (SSL)
echo "[5/8] Installing Nginx & Certbot..."
apt-get install -y nginx certbot python3-certbot-nginx

# 6. Configure Firewall (UFW)
echo "[6/8] Configuring UFW Firewall..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# 7. Create Application Directory & Set Permissions
echo "[7/8] Creating application directory at /var/www/carpet..."
mkdir -p /var/www/carpet
mkdir -p /var/www/carpet/logs
chown -R $SUDO_USER:$SUDO_USER /var/www/carpet || true

# 8. Configure Nginx Reverse Proxy
echo "[8/8] Setting up Nginx configuration..."
cat << 'EOF' > /etc/nginx/sites-available/carpet
server {
    listen 80;
    listen [::]:80;
    server_name _;

    client_max_body_size 50M;

    gzip on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript image/svg+xml;

    location /_next/static/ {
        alias /var/www/carpet/.next/static/;
        expires 365d;
        access_log off;
    }

    location /public/ {
        alias /var/www/carpet/public/;
        expires 30d;
        access_log off;
    }

    # Laravel storage assets
    location /storage/ {
        alias /var/www/carpet/backend/storage/app/public/;
        expires 30d;
        access_log off;
    }

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
EOF

ln -sf /etc/nginx/sites-available/carpet /etc/nginx/sites-enabled/carpet
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx

echo "=========================================================="
echo " Server Setup Completed Successfully!"
echo " Next Steps:"
echo " 1. Clone repository into /var/www/carpet"
echo " 2. Add .env files in /var/www/carpet/.env and /var/www/carpet/backend/.env"
echo " 3. Run: bash /var/www/carpet/scripts/deploy.sh"
echo " 4. To bind domain & SSL, run:"
echo "    certbot --nginx -d yourdomain.com -d www.yourdomain.com"
echo "=========================================================="
