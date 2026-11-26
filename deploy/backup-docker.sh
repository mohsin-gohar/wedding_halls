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
media_plain="$(mktemp "$BACKUP_DIR/.riwaj-storage-$stamp.XXXXXX.tar.gz")"
archive="$BACKUP_DIR/riwaj-grand-$stamp.sql.gz.age"
checksum="$archive.sha256"
media_archive="$BACKUP_DIR/riwaj-storage-$stamp.tar.gz.age"
media_checksum="$media_archive.sha256"
success=0
cleanup() {
  rm -f "$plain" "$media_plain"
  if [[ "$success" != "1" ]]; then
    rm -f "$archive" "$checksum" "$media_archive" "$media_checksum"
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
echo "Creating an archive of public and private uploaded files..."
docker compose exec -T app tar -czf - -C /var/www storage/app > "$media_plain"
test -s "$media_plain"

echo "Encrypting database and uploaded-file backups..."
age -r "$AGE_RECIPIENT" -o "$archive" "$plain"
age -r "$AGE_RECIPIENT" -o "$media_archive" "$media_plain"
test -s "$archive" && test -s "$media_archive"
(cd "$BACKUP_DIR" && sha256sum "$(basename "$archive")" > "$(basename "$checksum")")
(cd "$BACKUP_DIR" && sha256sum "$(basename "$media_archive")" > "$(basename "$media_checksum")")

echo "Copying encrypted archives and checksums off-site..."
for file in "$archive" "$checksum" "$media_archive" "$media_checksum"; do
  rclone copyto "$file" "$RCLONE_REMOTE/$(basename "$file")"
done
success=1

find "$BACKUP_DIR" -maxdepth 1 -type f \
  \( -name 'riwaj-grand-*.sql.gz.age' -o -name 'riwaj-grand-*.sql.gz.age.sha256' \
     -o -name 'riwaj-storage-*.tar.gz.age' -o -name 'riwaj-storage-*.tar.gz.age.sha256' \) \
  -mtime "+$RETENTION_DAYS" -delete

echo "Backup complete: $archive"
echo "Off-site copy: $RCLONE_REMOTE"
