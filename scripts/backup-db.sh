#!/bin/bash
# =============================================================================
# Automated Database Backup Script with Auto-Rotation (Keeps Last 14 Backups)
# =============================================================================
set -e

APP_DIR="${1:-/var/www/carpet}"
BACKUP_DIR="$APP_DIR/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

mkdir -p "$BACKUP_DIR"

echo "📦 Creating Pre-Deployment Database Snapshot..."

# 1. Backup SQLite if present
if [ -f "$APP_DIR/backend/database/database.sqlite" ]; then
    cp "$APP_DIR/backend/database/database.sqlite" "$BACKUP_DIR/db_sqlite_$TIMESTAMP.sqlite"
    echo "✅ SQLite Database backup created at $BACKUP_DIR/db_sqlite_$TIMESTAMP.sqlite"
fi

# 2. Backup MySQL if configured
if grep -q "DB_CONNECTION=mysql" "$APP_DIR/backend/.env" 2>/dev/null; then
    DB_NAME=$(grep DB_DATABASE "$APP_DIR/backend/.env" | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME "$APP_DIR/backend/.env" | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD "$APP_DIR/backend/.env" | cut -d '=' -f2)
    
    if [ -n "$DB_NAME" ] && command -v mysqldump > /dev/null 2>&1; then
        mysqldump -u"$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" > "$BACKUP_DIR/mysql_${DB_NAME}_$TIMESTAMP.sql"
        echo "✅ MySQL Database backup created at $BACKUP_DIR/mysql_${DB_NAME}_$TIMESTAMP.sql"
    fi
fi

# 3. Clean up older backups (keep last 14)
find "$BACKUP_DIR" -type f -mtime +14 -name "db_*" -delete 2>/dev/null || true

echo "📦 Database backup process completed."
