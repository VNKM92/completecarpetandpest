# 🚀 Enterprise CI/CD Pipeline & Deployment Guide

This project includes a **production-grade DevOps & CI/CD pipeline** combining **GitHub Actions**, **Automated SSH Zero-Downtime Deployment**, **Pre-Deploy Database Snapshots**, **Post-Deploy Smoke Tests**, and **Docker Containerization**.

---

## 🏗️ CI/CD Architecture Workflow

```mermaid
flowchart TD
    subgraph Developer Workflow
        A[Developer pushes code to branch] --> B[GitHub Repository]
    end

    subgraph GitHub Actions CI [Quality Gates]
        B --> C[Frontend CI: Node 20 + TypeScript + Next.js Build]
        B --> D[Backend CI: PHP 8.4 + Composer + PHPUnit Tests]
        B --> E[Security: npm audit + composer audit]
    end

    subgraph GitHub Actions CD [Production Release]
        C & D & E -->|All Passed on main branch| F[CD Deploy Workflow]
        F --> G[SSH Connect to VPS / Production Server]
        G --> H[Create Pre-Deploy DB Backup scripts/backup-db.sh]
        H --> I[Git Pull Latest Commit]
        I --> J[Run Laravel Migrations & Cache Optimization]
        J --> K[Build Next.js Production Bundle]
        K --> L[PM2 Zero-Downtime Process Reload]
        L --> M[Automated Smoke Test scripts/health-check.sh]
    end

    subgraph Error Handling
        M -->|Health Check Failed| N[Emergency Rollback scripts/rollback.sh]
        M -->|Success| O[Deployment Live 🟢]
    end
```

---

## 🔐 1. Required GitHub Secrets Setup

To enable automated Continuous Deployment on every push to `main`, configure these Repository Secrets in **GitHub ➔ Settings ➔ Secrets and variables ➔ Actions**:

| Secret Name | Description | Example Value |
| :--- | :--- | :--- |
| `SERVER_HOST` | Your VPS IP address or domain | `194.163.140.22` or `deploy.domain.com` |
| `SERVER_USER` | SSH Username (e.g. `root` or `deploy`) | `root` |
| `SERVER_SSH_KEY` | Private SSH Key (ed25519 or RSA) | `-----BEGIN OPENSSH PRIVATE KEY----- ...` |
| `SERVER_PORT` | SSH Port (default is 22) | `22` |
| `SERVER_PASSWORD`| (Optional if using SSH Key) SSH Password | `YourSecureSSHPassword` |

---

## 📋 2. Automated Pipeline Overview

### A. Continuous Integration (`.github/workflows/ci.yml`)
Triggers on every **Push** and **Pull Request** to `main` and `develop`:
1. **Next.js Frontend Quality Gate**:
   - Node.js 20 environment with npm caching.
   - Strict TypeScript type-checking (`tsc --noEmit`).
   - Production bundle compilation test (`npm run build`).
2. **Laravel Backend Quality Gate**:
   - PHP 8.4 environment with all necessary extensions (`sqlite3`, `mbstring`, `bcmath`, `curl`, `intl`, `zip`).
   - Composer dependency validation and cache.
   - Environment initialization and database migration verification.
   - Automated PHPUnit test execution (`php artisan test`).
3. **Security Audit**:
   - Node vulnerabilities (`npm audit`).
   - PHP Composer package vulnerabilities (`composer audit`).

### B. Continuous Deployment (`.github/workflows/deploy.yml`)
Triggers automatically when changes merge to `main` (or via manual `workflow_dispatch` button):
1. Runs full CI verification first.
2. Connects securely via SSH to your production server.
3. Automatically triggers [`scripts/deploy.sh`](file:///c:/xampp/htdocs/carpet/scripts/deploy.sh):
   - **Database Snapshot**: Runs `scripts/backup-db.sh` to preserve a snapshot before applying migrations.
   - **Zero-Downtime Reload**: Reloads PM2 processes without dropping active HTTP requests.
   - **Smoke Tests**: Executes `scripts/health-check.sh` to test `/up`, `/api/services`, and Next.js frontend availability.

---

## 🐳 3. Docker Deployment Alternative

If you prefer deploying with Docker containers instead of PM2 on your server:

### Local Multi-Container Development:
```bash
docker compose up --build
```
- Frontend: `http://localhost:3000`
- Backend API: `http://localhost:8000`
- Reverse Proxy: `http://localhost:80`

### Production Docker Stack:
```bash
docker compose -f docker-compose.prod.yml up -d --build
```

---

## 🛡️ 4. Maintenance & Operations Scripts

All operational scripts are organized inside [`scripts/`](file:///c:/xampp/htdocs/carpet/scripts/):

| Script | Purpose |
| :--- | :--- |
| `bash scripts/setup-server.sh` | 1-Click VPS initial server configuration (Node 20, PHP 8.4, Composer, Nginx, PM2, Certbot SSL) |
| `bash scripts/deploy.sh` | Automated deployment with zero-downtime PM2 reload |
| `bash scripts/backup-db.sh` | Snapshot current SQLite/MySQL database (retains last 14 backups) |
| `bash scripts/health-check.sh` | Automated post-deploy status checker |
| `bash scripts/rollback.sh` | Emergency 1-click rollback to previous Git commit and database snapshot |

---

## ⚡ 5. Manual Deployment (Without GitHub Actions)

If you wish to deploy directly on your server without Git triggers:
```bash
cd /var/www/carpet
bash scripts/deploy.sh
```
