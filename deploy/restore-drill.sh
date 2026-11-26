#!/usr/bin/env bash
# Restore a backup into a pre-created, EMPTY staging database on a separate
# restore target. This script refuses to target the configured production DB.
set -Eeuo pipefail
umask 077

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 /path/to/riwaj-grand-<timestamp>.sql.gz.age" >&2
  exit 2
fi

ARCHIVE="$1"
: "${AGE_IDENTITY:?Set AGE_IDENTITY to the private age identity file}"
: "${DB_DATABASE:?Set DB_DATABASE to the production database name}"
: "${DB_HOST:?Set DB_HOST to the configured production MySQL host}"
: "${RESTORE_DB_HOST:?Set RESTORE_DB_HOST to a separate staging MySQL host}"
: "${RESTORE_DB_DATABASE:?Set RESTORE_DB_DATABASE to a pre-created staging database}"
: "${RESTORE_DB_USERNAME:?Set RESTORE_DB_USERNAME}"
: "${RESTORE_DB_PASSWORD:?Set RESTORE_DB_PASSWORD}"
: "${RESTORE_CONFIRM:?Set RESTORE_CONFIRM to exactly RESTORE_DB_DATABASE}"

command -v age >/dev/null || { echo "age is required" >&2; exit 1; }
command -v gzip >/dev/null || { echo "gzip is required" >&2; exit 1; }
command -v mysql >/dev/null || { echo "mysql client is required" >&2; exit 1; }
test -r "$AGE_IDENTITY" || { echo "AGE_IDENTITY is not readable" >&2; exit 1; }
test -s "$ARCHIVE" || { echo "Backup archive is missing or empty" >&2; exit 1; }
command -v sha256sum >/dev/null || { echo "sha256sum is required" >&2; exit 1; }
test -s "$ARCHIVE.sha256" || { echo "Backup checksum file is missing" >&2; exit 1; }
(cd "$(dirname "$ARCHIVE")" && sha256sum -c "$(basename "$ARCHIVE").sha256")

if [[ "$RESTORE_DB_DATABASE" == "$DB_DATABASE" ]]; then
  echo "Refusing to restore into the production database" >&2
  exit 1
fi
if [[ "$RESTORE_DB_HOST" == "$DB_HOST" ]]; then
  echo "Refusing to restore onto the configured production database host" >&2
  exit 1
fi
if [[ ! "$RESTORE_DB_DATABASE" =~ _restore_drill$ ]]; then
  echo "Staging database name must end with _restore_drill" >&2
  exit 1
fi
if [[ "$RESTORE_CONFIRM" != "$RESTORE_DB_DATABASE" ]]; then
  echo "RESTORE_CONFIRM must exactly match the staging database name" >&2
  exit 1
fi

export MYSQL_PWD="$RESTORE_DB_PASSWORD"
tables="$(mysql --connect-timeout=10 -h "$RESTORE_DB_HOST" -u "$RESTORE_DB_USERNAME" -Nse \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()" "$RESTORE_DB_DATABASE")"
if [[ "$tables" != "0" ]]; then
  echo "Refusing to overwrite a non-empty staging database ($tables tables found)" >&2
  exit 1
fi

echo "Restoring encrypted backup to isolated staging database..."
age -d -i "$AGE_IDENTITY" "$ARCHIVE" | gzip -dc | \
  mysql --connect-timeout=10 -h "$RESTORE_DB_HOST" -u "$RESTORE_DB_USERNAME" "$RESTORE_DB_DATABASE"

tables="$(mysql --connect-timeout=10 -h "$RESTORE_DB_HOST" -u "$RESTORE_DB_USERNAME" -Nse \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()" "$RESTORE_DB_DATABASE")"
migrations="$(mysql --connect-timeout=10 -h "$RESTORE_DB_HOST" -u "$RESTORE_DB_USERNAME" -Nse \
  "SELECT COUNT(*) FROM migrations" "$RESTORE_DB_DATABASE")"

if [[ "$tables" -lt 1 || "$migrations" -lt 1 ]]; then
  echo "Restore verification failed: tables=$tables migrations=$migrations" >&2
  exit 1
fi

echo "Restore drill passed: $tables tables; $migrations migrations."
echo "Staging data was retained for manual integrity and application smoke checks."
unset MYSQL_PWD
