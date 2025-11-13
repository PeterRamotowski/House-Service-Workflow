#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${BLUE} Starting House Workflow Symfony 7 Application${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Default environment variables
export APP_ENV=${APP_ENV:-dev}
export APP_DEBUG=${APP_DEBUG:-1}
export DB_HOST=${DB_HOST:-house_workflow_db}
export DB_PORT=${DB_PORT:-5432}
export DB_NAME=${DB_NAME:-house_workflow}
export DB_USER=${DB_USER:-house_workflow}

# Wait for PostgreSQL to be ready
log_info "Waiting for PostgreSQL at $DB_HOST:$DB_PORT..."
max_attempts=30
attempt=0

while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
    attempt=$((attempt + 1))
    if [ $attempt -ge $max_attempts ]; then
        log_error "PostgreSQL failed to start within 30 seconds"
        exit 1
    fi
    sleep 1
    echo -n "."
done
echo ""
log_success "PostgreSQL is ready"

# Prepare Symfony directories with correct permissions
log_info "Preparing application directories..."
mkdir -p var/cache var/log var/run var/test
chown -R www-data:www-data var/
chmod -R 2775 var/

# Ensure public uploads directory exists
mkdir -p public/uploads
chown -R www-data:www-data public/uploads
chmod -R 2775 public/uploads
log_success "Directories prepared"

# Run database migrations in development
if [ "$APP_ENV" = "dev" ]; then
    log_info "Running database migrations..."
    if php bin/console doctrine:migrations:migrate --no-interaction 2>/dev/null; then
        log_success "Database migrations completed"
    else
        log_warn "Database migration skipped (may already be up to date)"
    fi
fi

# Validate database schema
if [ "$APP_ENV" = "dev" ]; then
    log_info "Validating database schema..."
    if php bin/console doctrine:schema:validate --no-interaction 2>/dev/null; then
        log_success "Database schema is valid"
    else
        log_warn "Schema validation issues detected (check logs)"
    fi
fi

# Warm up cache in production
if [ "$APP_ENV" = "prod" ]; then
    log_info "Warming up cache..."
    php bin/console cache:warmup --no-interaction
    log_success "Cache warmed"
fi

# Build assets (Symfony 7 AssetMapper)
if [ "$REBUILD_ASSETS" = "true" ] || [ "$APP_ENV" = "prod" ]; then
    log_info "Building assets with AssetMapper..."
    if php bin/console asset-map:compile 2>/dev/null; then
        log_success "Assets compiled"
    else
        log_warn "Asset compilation skipped (already current)"
    fi
fi

# Clear cache in dev (optional)
if [ "$CLEAR_CACHE" = "true" ]; then
    log_info "Clearing cache..."
    php bin/console cache:clear
    log_success "Cache cleared"
fi

# Load fixtures in development (optional)
if [ "$LOAD_FIXTURES" = "true" ] && [ "$APP_ENV" = "dev" ]; then
    log_info "Loading data fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction --append
    log_success "Fixtures loaded"
fi

# Verify PHP-FPM socket directory
log_info "Verifying PHP-FPM socket..."
mkdir -p /var/run/php-fpm
chown -R www-data:www-data /var/run/php-fpm
chmod -R 2775 /var/run/php-fpm

# Configure Nginx
log_info "Configuring Nginx..."
mkdir -p /etc/nginx/sites-available /etc/nginx/sites-enabled
ln -sf /etc/nginx/sites-available/default-host.conf /etc/nginx/sites-enabled/default-host.conf
log_success "Nginx configured"

# Create log directories with proper permissions
mkdir -p /var/log/nginx /var/log/php-fpm
chown -R www-data:www-data /var/log/nginx
chown -R www-data:www-data /var/log/php-fpm
chmod -R 2775 /var/log/nginx
chmod -R 2775 /var/log/php-fpm

echo ""
log_success "Pre-startup tasks completed"

# Start PHP-FPM
log_info "Starting PHP-FPM..."
/usr/local/sbin/php-fpm -F &
PHP_FPM_PID=$!
log_success "PHP-FPM started (PID: $PHP_FPM_PID)"

# Start Nginx
log_info "Starting Nginx..."
/usr/sbin/nginx -g "daemon off;" &
NGINX_PID=$!
log_success "Nginx started (PID: $NGINX_PID)"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN} House Workflow Application Started Successfully!${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e " Available at: http://localhost:1335"
echo -e " Database: $DB_NAME on $DB_HOST:$DB_PORT"
echo -e " Environment: $APP_ENV"
echo -e " Debug: $APP_DEBUG"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Trap signals for graceful shutdown
trap 'log_info "Shutting down gracefully..."; kill -TERM $PHP_FPM_PID $NGINX_PID; exit 0' TERM INT

# Wait for both processes to complete
wait $PHP_FPM_PID $NGINX_PID

log_success "Application stopped"
exit 0
