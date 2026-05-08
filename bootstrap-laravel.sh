#!/usr/bin/env bash
# bootstrap-laravel.sh — One-shot Laravel scaffold for Panda's api/ directory.
#
# Encodes skill-laravel-project-bootstrap.md (~/Desktop/uwc-web-co/00-skills/app-build/laravel/).
# Idempotent: safe to re-run; will skip steps already completed.
#
# Usage:
#   cd ~/Desktop/panda
#   ./bootstrap-laravel.sh
#
# Requires: PHP 8.3+, Composer 2.x, on PATH.

set -euo pipefail

readonly REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly API_DIR="${REPO_ROOT}/api"

err() { printf '\033[31mERROR:\033[0m %s\n' "$1" >&2; exit 1; }
log() { printf '\033[32m==>\033[0m %s\n' "$1"; }
warn() { printf '\033[33mWARN:\033[0m %s\n' "$1" >&2; }

# --- Preflight ----------------------------------------------------------------

log "Checking prerequisites..."
command -v php >/dev/null || err "PHP not on PATH. Run: brew install php@8.3"
command -v composer >/dev/null || err "Composer not on PATH. Run: brew install composer"

PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
[ "$PHP_VERSION" = "8.3" ] || [ "$PHP_VERSION" = "8.4" ] || err "PHP $PHP_VERSION found; need 8.3 or 8.4"
log "PHP $PHP_VERSION ✓"
log "Composer $(composer --version 2>&1 | head -1) ✓"

# --- 1. Laravel project scaffold ---------------------------------------------

if [ ! -d "$API_DIR" ]; then
    log "Creating Laravel 11 project in api/..."
    cd "$REPO_ROOT"
    composer create-project laravel/laravel api "11.*" --prefer-dist --no-interaction
else
    warn "api/ exists. Skipping composer create-project."
fi

cd "$API_DIR"

# --- 2. UWC standard packages -------------------------------------------------

log "Installing UWC standard composer packages..."
composer require \
    spatie/laravel-multitenancy:^4.0 \
    filament/filament:^3.2 \
    laravel/horizon:^5.30 \
    laravel/sanctum:^4.0 \
    brick/money:^0.10 \
    firebase/php-jwt:^6.10 \
    spatie/laravel-activitylog:^4.8 \
    sentry/sentry-laravel:^4.6 \
    league/flysystem-aws-s3-v3:^3.0 \
    opis/json-schema:^2.3 \
    barryvdh/laravel-dompdf:^3.0 \
    predis/predis:^2.2 \
    --no-interaction

log "Installing dev composer packages..."
composer require --dev \
    pestphp/pest:^3.0 \
    pestphp/pest-plugin-laravel:^3.0 \
    larastan/larastan:^2.9 \
    laravel/pint:^1.18 \
    --no-interaction

# --- 3. Pest init -------------------------------------------------------------

if [ ! -f "tests/Pest.php" ] || ! grep -q "use Tests\\\\TestCase" tests/Pest.php 2>/dev/null; then
    log "Initialising Pest..."
    ./vendor/bin/pest --init </dev/null || true
fi

# --- 4. Apply UWC configurations ---------------------------------------------

log "Applying Pint config (pint.json)..."
cat > pint.json <<'EOF'
{
    "preset": "laravel",
    "rules": {
        "ordered_imports": { "sort_algorithm": "alpha" },
        "no_unused_imports": true,
        "single_quote": true
    }
}
EOF

log "Applying PHPStan config (phpstan.neon)..."
cat > phpstan.neon <<'EOF'
includes:
    - ./vendor/larastan/larastan/extension.neon
parameters:
    paths: [app, config, routes, tests]
    level: 6
    ignoreErrors: []
    excludePaths: [./bootstrap/cache/*]
EOF

log "Writing .env.example (UWC standard)..."
cat > .env.example <<'EOF'
APP_NAME=Panda
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (Postgres only)
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=panda_local
DB_USERNAME=panda
DB_PASSWORD=changeme

# Redis (single instance, multiple DB indexes)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DB=0
REDIS_QUEUE_DB=1
REDIS_SESSION_DB=2

# Queue (Horizon-backed Redis in production; sync in tests)
QUEUE_CONNECTION=redis
HORIZON_PATH=horizon

# Cache + session
CACHE_STORE=redis
SESSION_DRIVER=redis

# Storage (R2 default, toggleable to s3 — per skill-laravel-storage-toggle)
STORAGE_BACKEND=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_ENDPOINT=https://<account>.r2.cloudflarestorage.com
R2_BUCKET=panda-media
R2_REGION=auto
S3_ACCESS_KEY_ID=
S3_SECRET_ACCESS_KEY=
S3_BUCKET=
S3_REGION=us-east-1

# Disease AI (mock in P1-P4, real Crop.health in P5)
DISEASE_AI_PROVIDER=mock
CROP_HEALTH_API_KEY=
CROP_HEALTH_MAX_MONTHLY_KES=20000

# Sentry
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1

# JWT (own auth — Panda is standalone; not the shared-Shira pattern)
JWT_SECRET=
EOF

log "Writing config/panda.php..."
cat > config/panda.php <<'EOF'
<?php

return [
    'storage_backend'           => env('STORAGE_BACKEND', 'r2'),
    'disease_ai_provider'       => env('DISEASE_AI_PROVIDER', 'mock'),
    'crop_health_api_key'       => env('CROP_HEALTH_API_KEY'),
    'crop_health_max_monthly_kes' => (int) env('CROP_HEALTH_MAX_MONTHLY_KES', 20000),
];
EOF

log "Updating filesystems.php (R2/S3 disks)..."
cat > config/filesystems.php <<'EOF'
<?php

return [
    'default' => env('STORAGE_BACKEND', 'r2'),

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => env('R2_REGION', 'auto'),
            'bucket' => env('R2_BUCKET'),
            'endpoint' => env('R2_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'visibility' => 'private',
            'throw' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('S3_ACCESS_KEY_ID'),
            'secret' => env('S3_SECRET_ACCESS_KEY'),
            'region' => env('S3_REGION', 'us-east-1'),
            'bucket' => env('S3_BUCKET'),
            'endpoint' => env('S3_ENDPOINT'),
            'use_path_style_endpoint' => false,
            'visibility' => 'private',
            'throw' => true,
        ],
    ],
];
EOF

# --- 5. Docker -----------------------------------------------------------------

log "Writing Dockerfile (PHP 8.3-FPM Alpine)..."
mkdir -p docker
cat > docker/Dockerfile <<'EOF'
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        postgresql-dev libpng-dev oniguruma-dev libzip-dev linux-headers \
        $PHPIZE_DEPS \
 && docker-php-ext-install pdo pdo_pgsql gd mbstring zip bcmath intl \
 && pecl install redis && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
EOF

cat > docker/nginx.conf <<'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

log "Writing docker-compose.yml..."
cat > docker-compose.yml <<'EOF'
services:
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=local
    depends_on: [db, redis]

  nginx:
    image: nginx:alpine
    ports: ["8000:80"]
    volumes:
      - .:/var/www/html
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on: [app]

  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: panda_local
      POSTGRES_USER: panda
      POSTGRES_PASSWORD: changeme
    ports: ["5432:5432"]
    volumes: [db_data:/var/lib/postgresql/data]

  redis:
    image: redis:7-alpine
    ports: ["6379:6379"]

  horizon:
    build:
      context: .
      dockerfile: docker/Dockerfile
    command: php artisan horizon
    volumes:
      - .:/var/www/html
    depends_on: [redis, db]

volumes:
  db_data:
EOF

# --- 6. Generate APP_KEY ------------------------------------------------------

if [ ! -f .env ]; then
    log "Generating .env from .env.example..."
    cp .env.example .env
    php artisan key:generate
fi

# --- 7. Filament + Horizon scaffolding ---------------------------------------

if [ ! -d "app/Filament" ]; then
    log "Installing Filament panel..."
    php artisan filament:install --panels --no-interaction || warn "Filament install needs manual completion"
fi

if [ ! -f "config/horizon.php" ]; then
    log "Publishing Horizon config..."
    php artisan horizon:install || warn "Horizon install needs manual completion"
fi

# --- 8. Sentry ----------------------------------------------------------------

if [ ! -f "config/sentry.php" ]; then
    log "Publishing Sentry config..."
    php artisan vendor:publish --provider="Sentry\\Laravel\\ServiceProvider" --no-interaction || warn "Sentry publish skipped"
fi

# --- Done ---------------------------------------------------------------------

log "✓ Bootstrap complete."
log ""
log "Next steps:"
log "  cd api"
log "  ./vendor/bin/pest          # run tests (should pass — Laravel default examples)"
log "  ./vendor/bin/pint --test   # check code style"
log "  ./vendor/bin/phpstan analyse  # static analysis"
log ""
log "When Docker is running:"
log "  docker compose up -d        # boot postgres + redis + app + nginx + horizon"
log "  curl http://localhost:8000/up"
log ""
log "Then commit:"
log "  cd $REPO_ROOT"
log "  git add api/ && git commit -m 'feat(api): scaffold Laravel 11 + UWC stack'"
log "  git push origin main"
