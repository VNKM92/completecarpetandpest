# Hostinger Deployment & Upgrade Guide

**Project Domain:** `https://brisbanecarpetpestexperts.com.au`  
**Brand:** Brisbane Carpet & Pest Experts

---

## 1. Quick Deployment Checklist

| Component | Setting | Value / Notes |
| :--- | :--- | :--- |
| **Domain** | Primary Domain | `brisbanecarpetpestexperts.com.au` |
| **Frontend** | Next.js 15 (Node.js) | Port `3000` (Managed via PM2 or Node App) |
| **Backend API** | Laravel 11 / PHP 8.2+ | Port `8000` (or served via PHP-FPM / PM2) |
| **Database** | MySQL / SQLite | Hostinger MySQL (`127.0.0.1:3306`) or SQLite |
| **SMTP Mail** | Hostinger Webmail | `smtp.hostinger.com`, Port `465` (SSL) |

---

## 2. Environment Configurations (`.env` Files)

### A. Root `.env` (Next.js Frontend & Prisma)
File: `/.env`
```env
# Database Connection (SQLite default, or Hostinger MySQL)
DATABASE_URL="file:./dev.db"
# Hostinger MySQL format:
# DATABASE_URL="mysql://u123456789_carpet:YourPassword@127.0.0.1:3306/u123456789_carpet"

JWT_SECRET="brisbane-carpet-pest-experts-secret-key-2026-super-secure"

# Application URLs
NEXT_PUBLIC_SITE_URL="https://brisbanecarpetpestexperts.com.au"
NEXT_PUBLIC_SITE_NAME="Brisbane Carpet & Pest Experts"
NEXT_PUBLIC_SITE_DESCRIPTION="Professional steam carpet cleaning, bond cleaning, end of lease, and certified pest control services in Brisbane, Queensland."

# Backend Laravel API URL
LARAVEL_API_URL="http://127.0.0.1:8000"
NEXT_PUBLIC_LARAVEL_URL="https://brisbanecarpetpestexperts.com.au"

# Email Configuration (Hostinger SMTP)
ADMIN_EMAIL="info@brisbanecarpetpestexperts.com.au"
EMAIL_FROM="Brisbane Carpet & Pest Experts <info@brisbanecarpetpestexperts.com.au>"
SMTP_HOST="smtp.hostinger.com"
SMTP_PORT="465"
SMTP_SECURE="true"
SMTP_USER="info@brisbanecarpetpestexperts.com.au"
SMTP_PASS="YourEmailPasswordHere"
```

---

### B. Backend `.env` (Laravel API)
File: `/backend/.env`
```env
APP_NAME="Brisbane Carpet & Pest Experts"
APP_ENV=production
APP_KEY=base64:liFRPf39bJDc9MC/uYgt1Qix4/SsrkdmNo60p9XYzK8=
APP_DEBUG=false
APP_URL=https://brisbanecarpetpestexperts.com.au
APP_TIMEZONE="Australia/Brisbane"

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

# Hostinger Database Configuration
# Option 1: SQLite (Instant, zero configuration)
DB_CONNECTION=sqlite

# Option 2: Hostinger MySQL (Recommended for high traffic)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=u123456789_carpet
# DB_USERNAME=u123456789_admin
# DB_PASSWORD=YourDatabasePasswordHere

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
CACHE_STORE=database

# Hostinger SMTP Mail Configuration
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=info@brisbanecarpetpestexperts.com.au
MAIL_PASSWORD=YourEmailPasswordHere
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="info@brisbanecarpetpestexperts.com.au"
MAIL_FROM_NAME="Brisbane Carpet & Pest Experts"
```

---

## 3. Database Setup in Hostinger hPanel

If using **Hostinger MySQL Database**:
1. Log in to **Hostinger hPanel**.
2. Go to **Databases** > **Management** > **Create a New MySQL Database and User**.
3. Create:
   - **Database Name**: e.g., `u123456789_carpet`
   - **Username**: e.g., `u123456789_admin`
   - **Password**: `YourStrongPassword`
4. Update the credentials in `backend/.env` and optionally in root `.env`.
5. Run the database migration and seeders:
   ```bash
   cd backend
   php artisan migrate --force
   php artisan db:seed --force
   ```

---

## 4. Deployment on Hostinger VPS / Cloud Server

### Step 1: Server Prerequisites
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nodejs npm php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-sqlite3 php8.2-zip unzip git nginx
sudo npm install -g pm2
```

### Step 2: Clone and Setup Project
```bash
cd /var/www
git clone <your-repo-url> carpet
cd /var/www/carpet

# 1. Install Node Dependencies and Build Next.js
npm ci
npm run build

# 2. Setup Backend Laravel
cd backend
composer install --no-dev --optimize-autoloader
php artisan key:generate --force # (if not already set)
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --force
cd ..

# 3. Permissions
sudo chown -R www-data:www-data /var/www/carpet
sudo chmod -R 775 /var/www/carpet/backend/storage /var/www/carpet/backend/bootstrap/cache
```

### Step 3: Start Application with PM2
```bash
# Start both Next.js frontend and Laravel backend with ecosystem.config.js
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

---

## 5. Nginx Reverse Proxy Configuration (Hostinger VPS)

File: `/etc/nginx/sites-available/brisbanecarpetpestexperts.com.au`

```nginx
server {
    listen 80;
    server_name brisbanecarpetpestexperts.com.au www.brisbanecarpetpestexperts.com.au;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name brisbanecarpetpestexperts.com.au www.brisbanecarpetpestexperts.com.au;

    # SSL Certificates (Managed by Hostinger / Certbot)
    ssl_certificate /etc/letsencrypt/live/brisbanecarpetpestexperts.com.au/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/brisbanecarpetpestexperts.com.au/privkey.pem;

    # Next.js App
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
    }

    # Laravel API Direct / Backend Proxy
    location /api {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Laravel Uploads & Storage
    location /storage {
        alias /var/www/carpet/backend/storage/app/public;
        try_files $uri $uri/ =404;
    }
}
```

Enable site & restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/brisbanecarpetpestexperts.com.au /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. Hostinger Email & Cron Setup

1. **Email Setup:**
   - In Hostinger hPanel -> **Emails** -> Create `info@brisbanecarpetpestexperts.com.au`.
   - Update `SMTP_PASS` in `.env` and `MAIL_PASSWORD` in `backend/.env`.

2. **Automated Reminders Cron Job:**
   Add to Hostinger **Cron Jobs** or crontab (`crontab -e`):
   ```cron
   * * * * * cd /var/www/carpet/backend && php artisan schedule:run >> /dev/null 2>&1
   ```
