#!/usr/bin/env bash
#
# Docker deploy script for MesApplisHF.
# Usage:  ./bin/deploy-docker.sh <prod|test|dev>
#
# Builds and (re)starts the Docker stack for one environment, then runs
# database migrations. Each environment is an isolated Compose project.
#
set -euo pipefail

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
log()  { echo -e "${GREEN}==>${NC} $1"; }
warn() { echo -e "${YELLOW}!!${NC} $1"; }
die()  { echo -e "${RED}Error:${NC} $1" >&2; exit 1; }

# --- Resolve environment argument -> branch -------------------------------
ENV="${1:-}"
case "$ENV" in
    prod) BRANCH="main" ;;
    test) BRANCH="release" ;;
    dev)  BRANCH="develop" ;;
    *) die "usage: $0 <prod|test|dev>" ;;
esac

PROJECT="mesapplishf-$ENV"
ENV_FILE=".env.docker.$ENV"

# --- Sanity checks --------------------------------------------------------
[ -f "composer.json" ] || die "must be run from the project root"
[ -f "$ENV_FILE" ]     || die "$ENV_FILE not found (copy it from $ENV_FILE.dist and fill in secrets)"
command -v docker >/dev/null 2>&1 || die "docker is not installed"

COMPOSE="docker compose -p $PROJECT --env-file $ENV_FILE"

log "Deploying '$ENV' stack from branch '$BRANCH'..."

log "Fetching latest code..."
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

log "Building image (target from $ENV_FILE)..."
$COMPOSE build

log "Starting containers..."
$COMPOSE up -d --remove-orphans

log "Waiting for the app container to be up..."
# depends_on waits for the DB healthcheck, so the app is ready to run console cmds.
$COMPOSE exec -T app php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

log "Pruning dangling images..."
docker image prune -f >/dev/null 2>&1 || true

DOMAIN=$(grep -E '^DEFAULT_URI=' "$ENV_FILE" | cut -d= -f2-)
echo ""
echo -e "${GREEN}✓ Deploy complete for '$ENV'${NC}"
echo "Stack: $PROJECT   |   Site: ${DOMAIN:-(see reverse proxy)}"
