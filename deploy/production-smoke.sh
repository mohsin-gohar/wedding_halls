#!/usr/bin/env bash
# Read-only production HTTP smoke checks. No credentials or mutations required.
set -Eeuo pipefail

: "${APP_URL:?Set APP_URL to the canonical https:// production URL}"
base="${APP_URL%/}"
if [[ "$base" != https://* ]]; then
  echo "APP_URL must use HTTPS" >&2
  exit 1
fi

tmp="$(mktemp)"
trap 'rm -f "$tmp"' EXIT

check_status() {
  local url="$1" expected="$2" code
  code="$(curl --silent --show-error --location --max-redirs 3 -o "$tmp" -w '%{http_code}' "$url")"
  if [[ "$code" != "$expected" ]]; then
    echo "Expected HTTP $expected from $url; got $code" >&2
    exit 1
  fi
  echo "PASS $code $url"
}

check_status "$base/up" 200
check_status "$base/ready" 200
check_status "$base/" 200

http_base="http://${base#https://}"
redirect_code="$(curl --silent --show-error -o "$tmp" -w '%{http_code}' "$http_base/")"
case "$redirect_code" in
  301|308) echo "PASS HTTP to HTTPS redirect ($redirect_code)" ;;
  *) echo "FAIL HTTP endpoint did not redirect to HTTPS (got $redirect_code)" >&2; exit 1 ;;
esac

for path in /.env /.git/config /composer.json; do
  code="$(curl --silent --show-error -o "$tmp" -w '%{http_code}' "$base$path")"
  case "$code" in
    403|404) echo "PASS protected path $path ($code)" ;;
    *) echo "FAIL protected path $path returned $code" >&2; exit 1 ;;
  esac
done

headers="$(curl --silent --show-error --head "$base/")"
for header in strict-transport-security x-content-type-options x-frame-options content-security-policy; do
  if ! grep -qi "^$header:" <<<"$headers"; then
    echo "FAIL missing security header: $header" >&2
    exit 1
  fi
  echo "PASS security header $header"
done

echo "Read-only production smoke checks passed."
