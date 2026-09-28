#!/usr/bin/env bash

set -Eeuo pipefail
umask 022

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

DEPLOY_DIR="${DEPLOY_DIR:-deploy-package}"
ENV_FILE="${ENV_FILE:-.env.production}"
DEPLOY_SYNC_R2="${DEPLOY_SYNC_R2:-1}"
R2_ASSET_BASE="${R2_ASSET_BASE:-}"
STATE_FILE="${R2_ASSET_STATE_FILE:-$DEPLOY_DIR/.last-r2-public-assets-sha}"

if [[ "$DEPLOY_SYNC_R2" == "0" ]]; then
    echo "==> Skip public R2 asset sync (DEPLOY_SYNC_R2=0)"
    exit 0
fi

for command_name in git php sed; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "ERROR: command '$command_name' tidak tersedia." >&2
        exit 1
    fi
done

if [[ "$(git rev-parse --is-inside-work-tree 2>/dev/null || true)" != "true" ]]; then
    echo "ERROR: asset sync harus dijalankan dari checkout Git GlassPos." >&2
    exit 1
fi

worktree_status="$(git status --porcelain --untracked-files=all)"
if [[ -n "$worktree_status" ]]; then
    echo "ERROR: working tree belum bersih. Asset R2 tidak akan disinkronkan dari source yang belum committed." >&2
    printf '%s\n' "$worktree_status" >&2
    exit 1
fi

head_sha="$(git rev-parse HEAD)"
mkdir -p "$DEPLOY_DIR"

env_value() {
    local key="$1"
    [[ -f "$ENV_FILE" ]] || return 0
    sed -n "s/^${key}=//p" "$ENV_FILE" | tail -n 1
}

strip_wrapping_quotes() {
    local value="$1"
    if [[ ${#value} -ge 2 ]]; then
        if [[ "$value" == \"*\" && "$value" == *\" ]]; then
            value="${value:1:${#value}-2}"
        elif [[ "$value" == \'*\' && "$value" == *\' ]]; then
            value="${value:1:${#value}-2}"
        fi
    fi
    printf '%s' "$value"
}

base_sha=""
base_source=""

if [[ -n "$R2_ASSET_BASE" ]]; then
    base_sha="$R2_ASSET_BASE"
    base_source="R2_ASSET_BASE"
elif [[ -s "$STATE_FILE" ]]; then
    base_sha="$(tr -d '[:space:]' < "$STATE_FILE")"
    base_source="$STATE_FILE"
else
    base_sha="$(strip_wrapping_quotes "$(env_value ASSET_VERSION)")"
    base_source="$ENV_FILE ASSET_VERSION"
fi

if [[ -z "$base_sha" ]] || ! git cat-file -e "${base_sha}^{commit}" 2>/dev/null; then
    echo "ERROR: baseline asset R2 belum tersedia atau bukan commit Git yang valid." >&2
    echo "Set R2_ASSET_BASE=<sha commit deployment terakhir> sekali, atau isi ASSET_VERSION pada $ENV_FILE dengan commit deployment yang valid." >&2
    exit 2
fi

base_sha="$(git rev-parse "${base_sha}^{commit}")"

if ! git merge-base --is-ancestor "$base_sha" "$head_sha"; then
    echo "ERROR: baseline R2 $base_sha ($base_source) bukan ancestor dari HEAD $head_sha." >&2
    echo "Set R2_ASSET_BASE ke commit deployment yang benar sebelum melanjutkan." >&2
    exit 2
fi

echo "==> Public R2 asset delta"
echo "base: $base_sha ($base_source)"
echo "head: $head_sha"

mapfile -t changed_files < <(
    git diff --no-renames --name-only --diff-filter=ACMTUXB "$base_sha" "$head_sha" -- public/assets
)
mapfile -t deleted_files < <(
    git diff --no-renames --name-only --diff-filter=D "$base_sha" "$head_sha" -- public/assets
)

upload_args=()
for file in "${changed_files[@]}"; do
    [[ "$file" == public/assets/* ]] || continue
    if [[ ! -f "$file" ]]; then
        echo "WARN: asset berubah tetapi tidak ada di working tree, dilewati: $file" >&2
        continue
    fi
    relative_path="${file#public/assets/}"
    upload_args+=("--path=$relative_path")
done

if [[ ${#upload_args[@]} -eq 0 ]]; then
    echo "==> Tidak ada public asset baru/berubah yang perlu diupload."
else
    echo "==> Upload ${#upload_args[@]} public asset berubah ke Cloudflare R2"
    php artisan r2:upload-public-assets "${upload_args[@]}"
fi

if [[ ${#deleted_files[@]} -gt 0 ]]; then
    echo "==> Catatan: ${#deleted_files[@]} asset terhapus dari repo tidak dihapus otomatis dari R2 (non-destructive)."
    for file in "${deleted_files[@]}"; do
        echo "   deleted-local: ${file#public/assets/}"
    done
fi

printf '%s\n' "$head_sha" > "$STATE_FILE"
echo "==> R2 asset baseline updated: $STATE_FILE -> $head_sha"
