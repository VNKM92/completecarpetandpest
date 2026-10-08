#!/bin/bash
# =============================================================================
# Automated Rollback Script
# Reverts to Previous Git Commit & Restores Previous Database Snapshot
# =============================================================================
set -e

APP_DIR="/var/www/carpet"
cd "$APP_DIR" || exit 1

echo "=========================================================="
echo "⚠️ INITIATING EMERGENCY ROLLBACK..."
echo "=========================================================="

# 1. Rollback Git to previous commit
echo "[1/4] Reverting Git commit..."
git reset --hard HEAD~1

# 2. Restore latest database snapshot if available
LATEST_SQLITE=$(ls -t "$APP_DIR/backups"/db_sqlite_*.sqlite 2>/dev/null | head -n 1 || true)
if [ -n "$LATEST_SQLITE" ] && [ -f "$LATEST_SQLITE" ]; then
    echo "[2/4] Restoring database from $LATEST_SQLITE..."
    cp "$LATEST_SQLITE" "$APP_DIR/backend/database/database.sqlite"
fi

# 3. Rebuild Next.js frontend
echo "[3/4] Rebuilding frontend bundle..."
npm run build

# 4. Restart PM2 processes
echo "[4/4] Restarting PM2 processes..."
pm2 reload ecosystem.config.js --update-env

echo "=========================================================="
echo "✅ ROLLBACK COMPLETED SUCCESSFULLY."
echo "=========================================================="
