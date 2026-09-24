#!/usr/bin/env bash
set -euo pipefail

# Gera os pacotes prontos para publicar na Hostinger:
# - dist/hostinger-public_html.zip: conteúdo de public/
# - dist/hostinger-app.zip: pasta app/ (raiz do zip), sem config/config.php nem storage/*.lock
#
# As entradas dos zips são sempre gravadas com separador `/` (padrão Linux/Hostinger),
# mesmo quando o fallback do Windows (sem `zip` disponível) é usado. O script verifica
# isso no final e falha se encontrar `\` ou faltar algum arquivo obrigatório.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

DIST_DIR="$ROOT_DIR/dist"
STAGE_DIR="$ROOT_DIR/.deploy-stage"

rm -rf "$DIST_DIR" "$STAGE_DIR"
mkdir -p "$DIST_DIR"
mkdir -p "$STAGE_DIR"

compress() {
    # compress <dir-com-o-conteudo-final> <caminho-do-zip>
    local src="$1"
    local zip_path="$2"

    rm -f "$zip_path"

    if command -v zip >/dev/null 2>&1; then
        (cd "$src" && zip -rq "$zip_path" .)
    else
        # Fallback Windows (sem `zip`): usa System.IO.Compression via um helper
        # PowerShell gerado em tempo de execução, para evitar inferno de aspas.
        # O helper normaliza os nomes das entradas para `/` e inclui arquivos
        # ocultos (dotfiles) como .htaccess.
        local win_src win_zip zip_dir zip_name helper
        win_src="$(cd "$src" && pwd -W 2>/dev/null || pwd)"
        zip_dir="$(cd "$(dirname "$zip_path")" && pwd -W 2>/dev/null || pwd)"
        zip_name="$(basename "$zip_path")"
        win_zip="${zip_dir}/${zip_name}"
        helper="$STAGE_DIR/zip-helper.ps1"

        cat >"$helper" <<'PS1EOF'
param([string]$Src, [string]$Zip)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

try {
    $srcFull = (Resolve-Path -LiteralPath $Src).Path.TrimEnd('\', '/') + [System.IO.Path]::DirectorySeparatorChar
    if (Test-Path -LiteralPath $Zip) {
        Remove-Item -LiteralPath $Zip -Force
    }
    $archive = [System.IO.Compression.ZipFile]::Open($Zip, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        Get-ChildItem -LiteralPath $srcFull -Recurse -File -Force | ForEach-Object {
            $rel = $_.FullName.Substring($srcFull.Length).Replace('\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $rel, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
        }
    } finally {
        $archive.Dispose()
    }
} catch {
    Write-Error $_
    exit 1
}
PS1EOF

        powershell -NoProfile -ExecutionPolicy Bypass -File "$helper" -Src "$win_src" -Zip "$win_zip"
    fi
}

verify_zip() {
    # verify_zip <zip_path> <entrada_obrigatoria> [entrada_obrigatoria...]
    local zip_path="$1"
    shift
    local entries

    if command -v unzip >/dev/null 2>&1; then
        entries="$(unzip -Z1 "$zip_path")"
    else
        local win_zip
        win_zip="$(cd "$(dirname "$zip_path")" && pwd -W 2>/dev/null || pwd)/$(basename "$zip_path")"
        entries="$(powershell -NoProfile -Command "
            Add-Type -AssemblyName System.IO.Compression.FileSystem
            \$a = [System.IO.Compression.ZipFile]::OpenRead('${win_zip}')
            try { \$a.Entries | ForEach-Object { \$_.FullName } } finally { \$a.Dispose() }
        ")"
    fi

    local bad_backslash
    bad_backslash="$(printf '%s\n' "$entries" | grep -F '\' || true)"
    if [ -n "$bad_backslash" ]; then
        echo "ERRO: $zip_path tem entradas com barra invertida:" >&2
        printf '%s\n' "$bad_backslash" >&2
        exit 1
    fi

    local bad_prefix
    bad_prefix="$(printf '%s\n' "$entries" | grep -E '^(\./|/)' || true)"
    if [ -n "$bad_prefix" ]; then
        echo "ERRO: $zip_path tem entradas com prefixo inválido (./ ou /):" >&2
        printf '%s\n' "$bad_prefix" >&2
        exit 1
    fi

    local required
    for required in "$@"; do
        if ! printf '%s\n' "$entries" | grep -Fxq "$required"; then
            echo "ERRO: $zip_path está faltando entrada obrigatória: $required" >&2
            exit 1
        fi
    done
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

verify_zip "$DIST_DIR/hostinger-public_html.zip" admin/index.php .htaccess _bootstrap.php
verify_zip "$DIST_DIR/hostinger-app.zip" app/bootstrap.php app/.htaccess

rm -rf "$STAGE_DIR"

echo "Pacotes gerados em $DIST_DIR:"
ls -la "$DIST_DIR"
