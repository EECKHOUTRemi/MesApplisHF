#!/usr/bin/env bash
#
# Deploy script for MesApplisHF
# Usage: run this on the server inside the project directory
#

set -euo pipefail  # exit on error, unset var, or pipe failure

# Colors for readable output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'  # No Color

log() {
    echo -e "${GREEN}==>${NC} $1"
}

warn() {
    echo -e "${YELLOW}!!${NC} $1"
}

# Make sure we're in the project root
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: must be run from project root${NC}"
    exit 1
fi

log "Pulling latest code from main branch..."
git pull origin main

log "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

log "Installing JavaScript vendor assets..."
sudo -u www-data php bin/console importmap:install

log "Running database migrations..."
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

log "Clearing and warming cache..."
sudo rm -rf /var/www/MesApplisHF/var/cache/*
sudo -u www-data php bin/console cache:clear --env=prod --no-warmup
sudo -u www-data php bin/console cache:warmup --env=prod

log "Fixing permissions..."
sudo chown -R deploy:www-data /var/www/MesApplisHF/var/
sudo chmod -R 2775 /var/www/MesApplisHF/var/

log "Reloading PHP-FPM (clears opcache)..."
sudo systemctl reload php8.4-fpm

echo ""
echo -e "${GREEN}✓ Deploy complete!${NC}"
echo "Site: https://mesapplishf.fr"