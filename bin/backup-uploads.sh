#!/usr/bin/env bash
#
# Uploads backup script for MesApplisHF (recipe images, profile pictures).
# Usage:  ./bin/backup-uploads.sh <prod|test|dev>
#
# Companion of bin/backup.sh (database): same destination (/srv/backups/<env>)
# and retention. Streams /app/public/uploads out of the running app container
# with `docker cp` (no bind mount, so it also works with snap-packaged Docker).
# Not encrypted: these files are publicly served by the site anyway.
#
# Called by bin/deploy-docker.sh before every restart, and meant for cron.
# Silent on success so cron only mails you on failure.
#
# Exit codes: 0 = backup written, 1 = error,
#             2 = no running app container (nothing to back up, e.g. first deploy).
set -euo pipefail

die() { echo "Error: $1" >&2; exit 1; }

# --- Resolve environment argument -> retention ----------------------------
ENV="${1:-}"
case "$ENV" in
    prod)     KEEP=30 ;;
    test|dev) KEEP=7  ;;
    *) die "usage: $0 <prod|test|dev>" ;;
esac

PROJECT="mesapplishf-$ENV"
DIR="/srv/backups/$ENV"
OUT="$DIR/mesapplishf_${ENV}_uploads_$(date +%Y%m%d-%H%M%S).tar.gz"

# --- Sanity checks --------------------------------------------------------
[ -d "$DIR" ] || die "$DIR not found"
command -v docker >/dev/null 2>&1 || die "docker is not installed"

# Find the app container through the Compose labels: no env file needed.
CONTAINER=$(docker ps -q \
    --filter "label=com.docker.compose.project=$PROJECT" \
    --filter "label=com.docker.compose.service=app" | head -n 1)

if [ -z "$CONTAINER" ]; then
    echo "No running app container for '$PROJECT': nothing to back up." >&2
    exit 2
fi

umask 077

# .tmp : renamed only once the archive is complete and readable, so a failed
#        run can never leave a plausible-looking backup behind.
trap 'rm -f "$OUT.tmp"' EXIT
docker cp "$CONTAINER:/app/public/uploads" - | gzip > "$OUT.tmp"
tar -tzf "$OUT.tmp" >/dev/null || die "archive is unreadable"
mv "$OUT.tmp" "$OUT"

# --- Retention ------------------------------------------------------------
# Same rule as bin/backup.sh: *_manual.* archives are kept indefinitely.
find "$DIR" -maxdepth 1 -name "mesapplishf_${ENV}_uploads_*.tar.gz" \
            ! -name '*_manual.*' \
            -mtime +"$KEEP" -delete
