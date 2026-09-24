#!/usr/bin/env bash
set -euo pipefail

# Gera os pacotes prontos para publicar na Hostinger:
# - dist/hostinger-public_html.zip: conteúdo de public/
# - dist/hostinger-app.zip: pasta app/ (raiz do zip), sem config/config.php nem storage/*.lock

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

DIST_DIR="$ROOT_DIR/dist"
STAGE_DIR="$ROOT_DIR/.deploy-stage"

rm -rf "$DIST_DIR" "$STAGE_DIR"
mkdir -p "$DIST_DIR"

compress() {
    # compress <dir-com-o-conteudo-final> <caminho-do-zip>
    local src="$1"
    local zip_path="$2"

    rm -f "$zip_path"

    if command -v zip >/dev/null 2>&1; then
        (cd "$src" && zip -rq "$zip_path" .)
    else
        local win_src win_zip zip_dir zip_name
        win_src="$(cd "$src" && pwd -W 2>/dev/null || pwd)"
        zip_dir="$(cd "$(dirname "$zip_path")" && pwd -W 2>/dev/null || pwd)"
        zip_name="$(basename "$zip_path")"
        win_zip="${zip_dir}/${zip_name}"
        powershell -NoProfile -Command "Compress-Archive -Path '${win_src}/*' -DestinationPath '${win_zip}' -Force"
    fi
}

# --- public_html: conteúdo de public/ ---
mkdir -p "$STAGE_DIR/public_html"
cp -r public/. "$STAGE_DIR/public_html/"
compress "$STAGE_DIR/public_html" "$DIST_DIR/hostinger-public_html.zip"

# --- app: pasta app/ na raiz do zip, sem config.php nem *.lock em storage/ ---
mkdir -p "$STAGE_DIR/app-root/app"
cp -r app/. "$STAGE_DIR/app-root/app/"
rm -f "$STAGE_DIR/app-root/app/config/config.php"
rm -f "$STAGE_DIR/app-root/app/storage/"*.lock 2>/dev/null || true
compress "$STAGE_DIR/app-root" "$DIST_DIR/hostinger-app.zip"

rm -rf "$STAGE_DIR"

echo "Pacotes gerados em $DIST_DIR:"
ls -la "$DIST_DIR"
