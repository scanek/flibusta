#!/bin/sh
set -e

# Поддержка запуска в фоне: ./getcovers.sh -d или ./getcovers.sh --background
if [ "$1" = "-d" ] || [ "$1" = "--background" ] || [ "$1" = "bg" ]; then
    LOG_FILE="getcovers.log"
    echo "Запуск getcovers.sh в фоне..."
    nohup "$0" __bg_exec > "$LOG_FILE" 2>&1 &
    PID=$!
    echo "Скрипт запущен в фоне с PID $PID."
    echo "Лог пишется в $LOG_FILE"
    echo "Для наблюдения за прогрессом выполните: tail -f $LOG_FILE"
    exit 0
fi

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