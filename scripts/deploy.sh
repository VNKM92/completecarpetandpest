#!/bin/bash
set -e

APP_DIR="/var/www/carpet"
echo "=========================================================="
echo " Starting Automated Zero-Downtime Deployment (Next.js + Laravel)"
echo " Date: $(date)"
echo "=========================================================="

# 1. Navigate to App Directory
cd "$APP_DIR" || { echo "Directory $APP_DIR not found"; exit 1; }

# 2. Pre-Deployment Database Snapshot
echo "[1/8] Creating pre-deployment database backup..."
bash scripts/backup-db.sh "$APP_DIR" || echo "Warning: Backup script finished with notice"

# 3. Pull Latest Changes from Git
echo "[2/8] Pulling latest code from Git..."
git pull origin main

# 4. Setup and Migrate Laravel Backend
echo "[3/8] Configuring Laravel Backend..."
cd "$APP_DIR/backend"
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
cd "$APP_DIR"

# 5. Install Next.js Frontend Dependencies
echo "[4/8] Installing Frontend dependencies..."
npm install --production=false

# 6. Build Next.js Production Bundle
echo "[5/8] Building Next.js application..."
npm run build

# 7. Ensure logs directory exists
mkdir -p logs

# 8. Reload Applications with PM2
echo "[6/8] Reloading PM2 processes with zero downtime..."
if pm2 describe carpet-frontend > /dev/null 2>&1; then
    pm2 reload ecosystem.config.js --update-env
else
    pm2 start ecosystem.config.js
fi

# 9. Save PM2 State
pm2 save

# 10. Post-Deployment Health Check
echo "[7/8] Running post-deployment health verification..."
bash scripts/health-check.sh

echo "=========================================================="
echo "🎉 Fullstack Deployment Completed Successfully!"
echo "   Frontend: http://127.0.0.1:3000"
echo "   Backend:  http://127.0.0.1:8000"
echo "=========================================================="
