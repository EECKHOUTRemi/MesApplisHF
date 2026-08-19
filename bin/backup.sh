#!/usr/bin/env bash
#
# Encrypted database backup script for MesApplisHF.
# Usage:  ./bin/backup.sh <prod|test|dev>
#
# Dumps one environment's database and pipes it straight into age, so the
# plaintext never lands on disk. Encryption is public-key: only the recipient
# in RECIPIENTS can decrypt, and that private key must NOT live on this server.
#
# Run from cron. Silent on success so cron only mails you on failure.
set -euo pipefail

die() { echo "Error: $1" >&2; exit 1; }

# --- Resolve environment argument -> retention ----------------------------
ENV="${1:-}"
case "$ENV" in
    prod)     KEEP=30 ;;
    test|dev) KEEP=7  ;;
    *) die "usage: $0 <prod|test|dev>" ;;
esac

STACK="/srv/mesapplishf/$ENV"
DIR="/srv/backups/$ENV"
RECIPIENTS="/srv/backups/recipients.txt"
ENV_FILE=".env.docker.$ENV"
OUT="$DIR/mesapplishf_${ENV}_$(date +%Y%m%d).dump.age"

# --- Sanity checks --------------------------------------------------------
[ -d "$STACK" ]       || die "$STACK not found"
[ -d "$DIR" ]         || die "$DIR not found"
[ -s "$RECIPIENTS" ]  || die "$RECIPIENTS missing or empty (age public key)"
command -v age >/dev/null 2>&1 || die "age is not installed"

# New dumps are created 0600: they are ciphertext, but the filename still
# leaks which environments exist and when they were taken.
umask 077
cd "$STACK"

# -Fc  : compressed custom format, restorable selectively with pg_restore.
# .tmp : renamed only once the whole pipeline succeeded, so a failed run can
#        never leave a plausible-looking backup behind.
# set -o pipefail (from set -euo above) is what makes a pg_dump failure
# propagate through the pipe instead of being masked by age's exit 0.
docker compose -p "mesapplishf-$ENV" --env-file "$ENV_FILE" \
    exec -T database pg_dump -U mesapplishf -Fc mesapplishf \
  | age -R "$RECIPIENTS" > "$OUT.tmp"

mv "$OUT.tmp" "$OUT"

# --- Retention ------------------------------------------------------------
# Scoped to this environment's encrypted dumps. Anything tagged *_manual.* is
# kept indefinitely: an ad-hoc dump was taken deliberately.
find "$DIR" -name "mesapplishf_${ENV}_*.dump.age" \
            ! -name '*_manual.*' \
            -mtime +"$KEEP" -delete
