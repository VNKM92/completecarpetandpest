@echo off
title Brisbane Carpet & Pest - Fullstack Launcher
echo ===================================================
echo Starting Brisbane Carpet & Pest Experts Fullstack
echo Backend: Laravel API on http://127.0.0.1:8000
echo Frontend: Next.js Admin & Web on http://localhost:3000
echo ===================================================
start "Laravel Backend API" cmd /k "cd backend && php artisan serve --port=8000"
timeout /t 2 /nobreak >nul
start "Next.js Frontend" cmd /k "npm run dev"
echo Both servers are launching!
pause
