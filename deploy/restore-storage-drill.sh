#!/usr/bin/env bash
# Restore uploaded files into a new, isolated staging directory.
set -Eeuo pipefail
umask 077

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 /path/to/riwaj-storage-<timestamp>.tar.gz.age" >&2
  exit 2
fi

ARCHIVE="$1"
: "${AGE_IDENTITY:?Set AGE_IDENTITY to the private age identity file}"
: "${PRODUCTION_STORAGE_DIR:?Set PRODUCTION_STORAGE_DIR to the live storage directory}"
: "${RESTORE_STORAGE_DIR:?Set RESTORE_STORAGE_DIR to a new staging directory}"
: "${RESTORE_CONFIRM:?Set RESTORE_CONFIRM to exactly the staging directory basename}"

command -v age >/dev/null || { echo "age is required" >&2; exit 1; }
command -v tar >/dev/null || { echo "tar is required" >&2; exit 1; }
command -v sha256sum >/dev/null || { echo "sha256sum is required" >&2; exit 1; }
command -v realpath >/dev/null || { echo "realpath is required" >&2; exit 1; }
test -r "$AGE_IDENTITY" || { echo "AGE_IDENTITY is not readable" >&2; exit 1; }
test -s "$ARCHIVE" || { echo "Storage archive is missing or empty" >&2; exit 1; }

production_path="$(realpath -m "$PRODUCTION_STORAGE_DIR")"
restore_path="$(realpath -m "$RESTORE_STORAGE_DIR")"
case "$restore_path" in
  "$production_path"|"$production_path"/*)
    echo "Refusing to restore files inside the live production storage tree" >&2
    exit 1
    ;;
esac

target_name="$(basename "$restore_path")"
if [[ "$target_name" != *_restore_drill ]]; then
  echo "Staging directory name must end in _restore_drill" >&2
  exit 1
fi
if [[ "$RESTORE_CONFIRM" != "$target_name" ]]; then
  echo "RESTORE_CONFIRM must exactly match the staging directory basename" >&2
  exit 1
fi
if [[ -e "$restore_path" ]]; then
  echo "Refusing to overwrite an existing staging directory" >&2
  exit 1
fi

test -s "$ARCHIVE.sha256" || { echo "Backup checksum file is missing" >&2; exit 1; }
(cd "$(dirname "$ARCHIVE")" && sha256sum -c "$(basename "$ARCHIVE").sha256")

mkdir -p "$(dirname "$restore_path")"
mkdir "$restore_path"

echo "Decrypting uploaded files into isolated staging storage..."
if ! age -d -i "$AGE_IDENTITY" "$ARCHIVE" | tar -xzf - --no-same-owner --no-same-permissions -C "$restore_path"; then
  echo "Storage restore failed; inspect and remove the staging directory manually." >&2
  exit 1
fi

if [[ ! -d "$restore_path/storage/app" ]]; then
  echo "Expected storage/app tree is missing from the archive." >&2
  exit 1
fi

file_count="$(find "$RESTORE_STORAGE_DIR/storage/app" -type f | wc -l | tr -d ' ')"
echo "Storage restore drill passed: $file_count files extracted to $RESTORE_STORAGE_DIR."
echo "Staging files were retained for manual integrity and access-control checks."
