#!/bin/sh
set -e

# Загружаем настройки из .env если файл существует
if [ -f ".env" ]; then
    set -a
    . ./.env
    set +a
fi

CACHE_DIR="${CACHE_DIR:-./cache}"
BASE_URL="${FLIBUSTA_SQL_URL:-https://flibusta.is/sql/}"

mkdir -p "$CACHE_DIR"

echo "Downloading covers and author photos from $BASE_URL..."
wget --directory-prefix="$CACHE_DIR" -c -nc "${BASE_URL}lib.a.attached.zip" || true
wget --directory-prefix="$CACHE_DIR" -c -nc "${BASE_URL}lib.b.attached.zip" || true

echo "Covers download finished."