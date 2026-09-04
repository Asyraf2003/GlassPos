#!/usr/bin/env bash

set -Eeuo pipefail
umask 022

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

APP_NAME="${APP_NAME:-glasspos-cpanel}"
APP_DIR_NAME="${APP_DIR_NAME:-glasspos}"
PUBLIC_DIR_NAME="${PUBLIC_DIR_NAME:-public_html}"
DEPLOY_DIR="${DEPLOY_DIR:-deploy-package}"
SITE_URL="${SITE_URL:-https://arbiconbengkel.my.id}"
SITE_URL="${SITE_URL%/}"
ENV_FILE="${ENV_FILE:-.env.production}"

validate_name() {
    local label="$1"
    local value="$2"
    if [[ -z "$value" || "$value" == "." || "$value" == ".." || ! "$value" =~ ^[A-Za-z0-9._-]+$ ]]; then
        echo "ERROR: $label hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda minus." >&2
        exit 1
    fi
}

env_value() {
    local key="$1"
    sed -n "s/^${key}=//p" "$ENV_FILE" | tail -n 1
}

validate_name "APP_NAME" "$APP_NAME"
validate_name "APP_DIR_NAME" "$APP_DIR_NAME"
validate_name "PUBLIC_DIR_NAME" "$PUBLIC_DIR_NAME"
validate_name "DEPLOY_DIR" "$DEPLOY_DIR"

for command_name in git php composer rsync zip unzip sed grep; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "ERROR: command '$command_name' tidak tersedia." >&2
        exit 1
    fi
done

if [[ "$(git rev-parse --is-inside-work-tree 2>/dev/null || true)" != "true" ]]; then
    echo "ERROR: builder harus dijalankan dari checkout Git GlassPos." >&2
    exit 1
fi

worktree_status="$(git status --porcelain --untracked-files=all)"
if [[ -n "$worktree_status" ]]; then
    echo "ERROR: working tree belum bersih. Commit, pindahkan, atau hapus file berikut sebelum membuat paket:" >&2
    printf '%s\n' "$worktree_status" >&2
    exit 1
fi

for required_file in \
    artisan \
    composer.json \
    composer.lock \
    "$ENV_FILE" \
    public/index.php \
    public/.htaccess \
    deploy/cpanel/index.php.template \
    deploy/cpanel/clear.php.template; do
    if [[ ! -f "$required_file" ]]; then
        echo "ERROR: file wajib tidak ditemukan: $required_file" >&2
        exit 1
    fi
done

if [[ "$(env_value APP_ENV)" != "production" ]]; then
    echo "ERROR: $ENV_FILE harus memiliki APP_ENV=production." >&2
    exit 1
fi
if [[ "$(env_value APP_DEBUG)" != "false" ]]; then
    echo "ERROR: $ENV_FILE harus memiliki APP_DEBUG=false." >&2
    exit 1
fi
if [[ -z "$(env_value APP_KEY)" ]]; then
    echo "ERROR: APP_KEY pada $ENV_FILE kosong." >&2
    exit 1
fi
if [[ -z "$(env_value DB_DATABASE)" || -z "$(env_value DB_USERNAME)" ]]; then
    echo "ERROR: DB_DATABASE dan DB_USERNAME pada $ENV_FILE wajib terisi." >&2
    exit 1
fi

mkdir -p "$DEPLOY_DIR"

next_id=1
while [[ -e "$DEPLOY_DIR/$APP_NAME-$(printf '%03d' "$next_id").zip" ]]; do
    next_id=$((next_id + 1))
done

next_id_padded="$(printf '%03d' "$next_id")"
zip_file="$DEPLOY_DIR/$APP_NAME-$next_id_padded.zip"
setup_file="$DEPLOY_DIR/$APP_NAME-$next_id_padded-setup.txt"
stage_dir="$(mktemp -d "${TMPDIR:-/tmp}/$APP_NAME-deploy-$next_id_padded.XXXXXX")"
app_stage="$stage_dir/$APP_DIR_NAME"
public_stage="$stage_dir/$PUBLIC_DIR_NAME"

cleanup() {
    rm -rf "$stage_dir"
}
trap cleanup EXIT

echo "==> Clear local Laravel caches"
php artisan optimize:clear >/dev/null

echo "==> Stage Laravel root: $APP_DIR_NAME/"
mkdir -p "$app_stage" "$public_stage"

rsync -a ./ "$app_stage/" \
    --exclude "/.git/" \
    --exclude "/.github/" \
    --exclude "/.idea/" \
    --exclude "/.vscode/" \
    --exclude "/.zed/" \
    --exclude "/.env" \
    --exclude "/.env.*" \
    --exclude "/auth.json" \
    --exclude "/vendor/" \
    --exclude "/tests/" \
    --exclude "/deploy/" \
    --exclude "/scripts/" \
    --exclude "/$DEPLOY_DIR/" \
    --exclude "/public/" \
    --exclude "/storage/app/public/***" \
    --exclude "/storage/app/private/***" \
    --exclude "/storage/framework/cache/data/***" \
    --exclude "/storage/framework/sessions/***" \
    --exclude "/storage/framework/testing/***" \
    --exclude "/storage/framework/views/***" \
    --exclude "/storage/logs/***" \
    --exclude "/bootstrap/cache/*.php" \
    --exclude "/database/*.sqlite" \
    --exclude "/database/*.sqlite-shm" \
    --exclude "/database/*.sqlite-wal" \
    --exclude "/.phpunit.cache/" \
    --exclude "/.phpunit.result.cache" \
    --exclude "/phpunit.xml" \
    --exclude "/Makefile" \
    --exclude "/mk/" \
    --exclude "/docs/" \
    --exclude "/README.md" \
    --exclude "/README_SETUP.md" \
    --exclude "/README_TECHNICAL.md" \
    --exclude "/AGENTS.md" \
    --exclude "/LICENSE.md"

cp "$ENV_FILE" "$app_stage/.env"
chmod 0600 "$app_stage/.env"

mkdir -p \
    "$app_stage/bootstrap/cache" \
    "$app_stage/storage/app/private" \
    "$app_stage/storage/app/public" \
    "$app_stage/storage/framework/cache/data" \
    "$app_stage/storage/framework/sessions" \
    "$app_stage/storage/framework/testing" \
    "$app_stage/storage/framework/views" \
    "$app_stage/storage/logs"

echo "==> Stage public document root: $PUBLIC_DIR_NAME/"
rsync -a public/ "$public_stage/" \
    --exclude "/storage" \
    --exclude "/storage/***"

sed "s/__APP_DIR_NAME__/$APP_DIR_NAME/g" \
    deploy/cpanel/index.php.template > "$public_stage/index.php"

clear_token="$(php -r 'echo bin2hex(random_bytes(32));')"
clear_token_hash="$(php -r 'echo hash("sha256", $argv[1]);' "$clear_token")"
clear_file_name="clear-${clear_token_hash:0:16}.php"

sed \
    -e "s/__APP_DIR_NAME__/$APP_DIR_NAME/g" \
    -e "s/__DEPLOY_TOKEN_HASH__/$clear_token_hash/g" \
    deploy/cpanel/clear.php.template > "$public_stage/$clear_file_name"

php -l "$public_stage/index.php" >/dev/null
php -l "$public_stage/$clear_file_name" >/dev/null

echo "==> Install production Composer dependencies"
(
    cd "$app_stage"
    COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist
)

rm -f \
    "$app_stage/bootstrap/cache/config.php" \
    "$app_stage/bootstrap/cache/events.php" \
    "$app_stage"/bootstrap/cache/routes*.php

echo "==> Normalize shared-hosting permissions"
find "$app_stage" "$public_stage" -type d -exec chmod 0755 {} +
find "$app_stage" "$public_stage" -type f -exec chmod 0644 {} +
chmod 0755 "$app_stage/artisan"
chmod 0600 "$app_stage/.env"

echo "==> Create ZIP: $zip_file"
zip_file_absolute="$ROOT_DIR/$zip_file"
(
    cd "$stage_dir"
    zip -qr "$zip_file_absolute" "$APP_DIR_NAME" "$PUBLIC_DIR_NAME"
)

echo "==> Verify deployment package"
bash scripts/verify-cpanel-package.sh \
    "$zip_file" \
    "$APP_DIR_NAME" \
    "$PUBLIC_DIR_NAME" \
    "$SITE_URL" \
    "$clear_file_name"

{
    echo "GlassPos cPanel deployment"
    echo "ZIP: $(basename "$zip_file")"
    echo "Extract the ZIP directly into the cPanel home directory."
    echo "Packaged environment: $APP_DIR_NAME/.env (copied from local $ENV_FILE)."
    echo "After extraction, open this one-time maintenance URL:"
    echo "URL: $SITE_URL/$clear_file_name?token=$clear_token"
    echo "$clear_file_name clears pre-bootstrap caches, refreshes web OPcache when enabled, verifies private-R2 presigning,"
    echo "then runs optimize:clear, migrate --force, optimize, and deletes itself after success."
} > "$setup_file"

chmod 600 "$zip_file" "$setup_file"

echo "==> Done"
echo "ZIP: $zip_file"
echo "SETUP: $setup_file"
echo "Keep both files private; the ZIP contains production credentials and setup contains the one-time token."
