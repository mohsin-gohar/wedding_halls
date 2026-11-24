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
: "${RESTORE_STORAGE_DIR:?Set RESTORE_STORAGE_DIR to a new staging directory}"
: "${RESTORE_CONFIRM:?Set RESTORE_CONFIRM to exactly the staging directory basename}"

command -v age >/dev/null || { echo "age is required" >&2; exit 1; }
command -v tar >/dev/null || { echo "tar is required" >&2; exit 1; }
command -v sha256sum >/dev/null || { echo "sha256sum is required" >&2; exit 1; }
test -r "$AGE_IDENTITY" || { echo "AGE_IDENTITY is not readable" >&2; exit 1; }
test -s "$ARCHIVE" || { echo "Storage archive is missing or empty" >&2; exit 1; }

target_name="$(basename "$RESTORE_STORAGE_DIR")"
if [[ "$target_name" != *_restore_drill ]]; then
  echo "Staging directory name must end in _restore_drill" >&2
  exit 1
fi
if [[ "$RESTORE_CONFIRM" != "$target_name" ]]; then
  echo "RESTORE_CONFIRM must exactly match the staging directory basename" >&2
  exit 1
fi
if [[ -e "$RESTORE_STORAGE_DIR" ]]; then
  echo "Refusing to overwrite an existing staging directory" >&2
  exit 1
fi

if [[ -f "$ARCHIVE.sha256" ]]; then
  (cd "$(dirname "$ARCHIVE")" && sha256sum -c "$(basename "$ARCHIVE").sha256")
fi

mkdir -p "$(dirname "$RESTORE_STORAGE_DIR")"
mkdir "$RESTORE_STORAGE_DIR"

echo "Decrypting uploaded files into isolated staging storage..."
if ! age -d -i "$AGE_IDENTITY" "$ARCHIVE" | tar -xzf - --no-same-owner --no-same-permissions -C "$RESTORE_STORAGE_DIR"; then
  echo "Storage restore failed; inspect and remove the staging directory manually." >&2
  exit 1
fi

if [[ ! -d "$RESTORE_STORAGE_DIR/storage/app" ]]; then
  echo "Expected storage/app tree is missing from the archive." >&2
  exit 1
fi

file_count="$(find "$RESTORE_STORAGE_DIR/storage/app" -type f | wc -l | tr -d ' ')"
echo "Storage restore drill passed: $file_count files extracted to $RESTORE_STORAGE_DIR."
echo "Staging files were retained for manual integrity and access-control checks."
