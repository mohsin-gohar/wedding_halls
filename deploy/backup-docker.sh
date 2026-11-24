#!/usr/bin/env bash
# Encrypted, consistent MySQL backup for the Docker Compose deployment.
# Required: age, rclone, Docker Compose, AGE_RECIPIENT, RCLONE_REMOTE.
set -Eeuo pipefail
umask 077

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

: "${AGE_RECIPIENT:?Set AGE_RECIPIENT to the age public recipient}"
: "${RCLONE_REMOTE:?Set RCLONE_REMOTE to an off-site rclone destination (remote:path)}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/riwaj-grand}"

command -v age >/dev/null || { echo "age is required" >&2; exit 1; }
command -v rclone >/dev/null || { echo "rclone is required for off-site backup" >&2; exit 1; }
docker compose config -q
mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

stamp="$(date -u +%Y%m%dT%H%M%SZ)"
plain="$(mktemp "$BACKUP_DIR/.riwaj-grand-$stamp.XXXXXX.sql.gz")"
archive="$BACKUP_DIR/riwaj-grand-$stamp.sql.gz.age"
checksum="$archive.sha256"
success=0
cleanup() {
  rm -f "$plain"
  if [[ "$success" != "1" ]]; then
    rm -f "$archive" "$checksum"
  fi
}
trap cleanup EXIT

echo "Creating a transaction-consistent database dump..."
docker compose exec -T db sh -ec '
  MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot \
    --single-transaction --quick --routines --triggers --events --hex-blob \
    "$MYSQL_DATABASE"
' | gzip -c > "$plain"

test -s "$plain"
echo "Encrypting backup..."
age -r "$AGE_RECIPIENT" -o "$archive" "$plain"
test -s "$archive"
(cd "$BACKUP_DIR" && sha256sum "$(basename "$archive")" > "$(basename "$checksum")")

echo "Copying encrypted archive and checksum off-site..."
rclone copyto "$archive" "$RCLONE_REMOTE/$(basename "$archive")"
rclone copyto "$checksum" "$RCLONE_REMOTE/$(basename "$checksum")"
success=1

find "$BACKUP_DIR" -maxdepth 1 -type f \
  \( -name 'riwaj-grand-*.sql.gz.age' -o -name 'riwaj-grand-*.sql.gz.age.sha256' \) \
  -mtime "+$RETENTION_DAYS" -delete

echo "Backup complete: $archive"
echo "Off-site copy: $RCLONE_REMOTE"
