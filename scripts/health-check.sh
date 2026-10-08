#!/bin/bash
# =============================================================================
# Automated Health Check & Smoke Test Script
# =============================================================================
set -e

echo "🔍 Running Post-Deployment Health Checks..."

# 1. Wait for services to warm up
sleep 3

# 2. Check Laravel Backend Health
echo -n "Checking Laravel Backend (/up)... "
LARAVEL_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/up || echo "000")
if [ "$LARAVEL_STATUS" -eq 200 ] || [ "$LARAVEL_STATUS" -eq 302 ]; then
    echo "✅ UP (HTTP $LARAVEL_STATUS)"
else
    echo "❌ FAILED (HTTP $LARAVEL_STATUS)"
    exit 1
fi

# 3. Check Laravel Services API Endpoint
echo -n "Checking Public Services API (/api/services)... "
API_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/api/services || echo "000")
if [ "$API_STATUS" -eq 200 ]; then
    echo "✅ UP (HTTP $API_STATUS)"
else
    echo "❌ FAILED (HTTP $API_STATUS)"
    exit 1
fi

# 4. Check Next.js Frontend Server
echo -n "Checking Next.js Frontend (http://127.0.0.1:3000)... "
FRONTEND_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:3000 || echo "000")
if [ "$FRONTEND_STATUS" -eq 200 ] || [ "$FRONTEND_STATUS" -eq 304 ] || [ "$FRONTEND_STATUS" -eq 307 ] || [ "$FRONTEND_STATUS" -eq 308 ]; then
    echo "✅ UP (HTTP $FRONTEND_STATUS)"
else
    echo "❌ FAILED (HTTP $FRONTEND_STATUS)"
    exit 1
fi

echo "=========================================================="
echo "🎉 ALL SYSTEM HEALTH CHECKS PASSED!"
echo "=========================================================="
