#!/usr/bin/env bash
# =====================================================================
#  repair-kiosk :: project scaffold
#  Usage:  bash create-tree.sh            (creates ./repair-kiosk)
#          bash create-tree.sh myname     (creates ./myname)
# =====================================================================
set -e

ROOT="${1:-repair-kiosk}"

if [ -d "$ROOT" ]; then
  echo "!! Directory '$ROOT' already exists. Aborting so nothing is overwritten."
  exit 1
fi

echo ">> Creating project root: $ROOT"
mkdir -p "$ROOT"
cd "$ROOT"

# ---------------------------------------------------------------- dirs
echo ">> Creating directories..."
mkdir -p \
  public/assets/{css,js,img,fonts} \
  app/Config \
  app/Core \
  app/Middleware \
  app/Models \
  app/Controllers/{Kiosk,Staff,Api} \
  app/Services \
  app/Views/{layouts,partials,errors} \
  app/Views/kiosk/{dropoff,collect,errors} \
  app/Views/staff/{jobs,equipment,borrowers,users,reports} \
  app/Views/public \
  app/Views/print \
  app/Helpers \
  database/{migrations,seeds} \
  storage/{uploads/jobs,qrcodes,logs,cache,backups} \
  docs \
  tests/fixtures \
  scripts

# ---------------------------------------------------------------- files
echo ">> Creating placeholder files..."

# public/
touch public/index.php public/media.php public/favicon.ico
touch public/assets/css/{kiosk,staff,track,print}.css
touch public/assets/js/{scanner,kiosk-dropoff,kiosk-collect,idle-reset,signature-pad,staff-jobs}.js

# app/Config
touch app/Config/{app,database,routes,statuses}.php

# app/Core
touch app/Core/{Router,Controller,Model,Database,Request,Response,Session,Csrf,Validator,Auth}.php

# app/Middleware
touch app/Middleware/{AuthMiddleware,RoleMiddleware,KioskMiddleware,CsrfMiddleware}.php

# app/Models
touch app/Models/{Equipment,Borrower,RepairJob,JobStatusHistory,JobAccessory,JobPhoto,JobNote,User,AuditLog}.php

# app/Controllers
touch app/Controllers/PublicController.php
touch app/Controllers/Kiosk/{HomeController,DropoffController,CollectController,PrintController}.php
touch app/Controllers/Staff/{AuthController,DashboardController,JobController,EquipmentController,BorrowerController,UserController,ReportController}.php
touch app/Controllers/Api/{ScanController,JobStatusController}.php

# app/Services
touch app/Services/{JobService,TokenService,QrService,BarcodeService,SlipService,PhotoService,NotificationService}.php

# app/Views
touch app/Views/layouts/{kiosk,staff,public,print}.php
touch app/Views/partials/{nav,flash,status-badge,scanner-input}.php
touch app/Views/errors/{404,500}.php
touch app/Views/kiosk/home.php
touch app/Views/kiosk/dropoff/{scan,identify,fault,accessories,confirm,success}.php
touch app/Views/kiosk/collect/{scan,verify,sign,success}.php
touch app/Views/kiosk/errors/{already-open,not-ready,unknown-asset}.php
touch app/Views/staff/{login,dashboard}.php
touch app/Views/staff/jobs/{index,show,update-status}.php
touch app/Views/staff/equipment/{index,form}.php
touch app/Views/staff/borrowers/{index,form}.php
touch app/Views/staff/users/{index,form}.php
touch app/Views/staff/reports/index.php
touch app/Views/public/{track,not-found}.php
touch app/Views/print/{dropoff-slip,handover-slip}.php

# app/Helpers
touch app/Helpers/{functions.php,DateHelper.php,StringHelper.php}

# database
touch database/schema.sql
touch database/migrations/{001_create_users,002_create_equipment,003_create_borrowers,004_create_repair_jobs,005_create_job_status_history,006_create_job_accessories,007_create_job_photos,008_create_job_notes,009_create_audit_log}.sql
touch database/seeds/{users_seed,equipment_seed,accessories_seed}.sql

# docs / tests / scripts
touch docs/{state-machine.md,setup.md,scanner-config.md,printer-config.md,user-manual.md}
touch tests/{JobServiceTest.php,TokenServiceTest.php}
touch scripts/backup-db.sh

# ---------------------------------------------- keep empty dirs in git
for d in storage/uploads/jobs storage/qrcodes storage/logs storage/cache \
         storage/backups public/assets/img public/assets/fonts tests/fixtures; do
  touch "$d/.gitkeep"
done

# ---------------------------------------------------------------- seeds
echo ">> Writing starter config files..."

cat > .gitignore <<'EOF'
/vendor/
/node_modules/
.env
storage/uploads/*
!storage/uploads/.gitkeep
storage/qrcodes/*
!storage/qrcodes/.gitkeep
storage/logs/*
!storage/logs/.gitkeep
storage/cache/*
!storage/cache/.gitkeep
storage/backups/*
!storage/backups/.gitkeep
.DS_Store
Thumbs.db
.idea/
*.log
EOF

cat > .env.example <<'EOF'
APP_NAME="Repair Workshop Kiosk"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/repair-kiosk/public
APP_TIMEZONE=Asia/Colombo

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=repair_kiosk
DB_USER=kiosk_app
DB_PASS=change-me
DB_CHARSET=utf8mb4

KIOSK_IDLE_SECONDS=90
KIOSK_ALLOWED_IPS=127.0.0.1
TRACK_BASE_URL=http://localhost/repair-kiosk/public/track
EOF

cat > public/.htaccess <<'EOF'
RewriteEngine On

# Serve real files/directories directly
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Everything else goes to the front controller
RewriteRule ^ index.php [L]

# Block dotfiles
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
EOF

cat > .htaccess <<'EOF'
# Nothing above public/ should ever be reachable by URL
Require all denied
EOF

cat > README.md <<'EOF'
# Repair Workshop Self-Service Kiosk

Self-service drop-off and collection terminal for an equipment repair workshop.
Barcode scan in, QR tracking slip out.

## Stack
PHP 8.x - MySQL 8.x - vanilla JS - HID barcode scanner

## Setup
1. `composer install`
2. `cp .env.example .env` and fill in DB credentials
3. `mysql -u root -p < database/schema.sql`
4. Point the web server document root at `public/`

## Default login
admin / Admin@123 - change immediately.
EOF

cat > composer.json <<'EOF'
{
    "name": "workshop/repair-kiosk",
    "description": "Self-service repair workshop kiosk",
    "type": "project",
    "require": {
        "php": ">=8.0",
        "endroid/qr-code": "^4.8",
        "vlucas/phpdotenv": "^5.6"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        },
        "files": [
            "app/Helpers/functions.php"
        ]
    }
}
EOF

cat > public/index.php <<'EOF'
<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

echo 'Repair Kiosk skeleton is alive.';
EOF

chmod -R 775 storage 2>/dev/null || true
chmod +x scripts/*.sh 2>/dev/null || true

echo ""
echo "==============================================="
echo " Done. Created $(find . -type d | wc -l) directories and $(find . -type f | wc -l) files."
echo "==============================================="
echo ""
echo " Next steps:"
echo "   cd $ROOT"
echo "   git init && git add -A && git commit -m 'scaffold project structure'"
echo "   composer install"
echo "   cp .env.example .env"
echo "   code ."
echo ""