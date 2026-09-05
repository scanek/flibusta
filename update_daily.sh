#!/bin/sh
set -e

# Поддержка запуска в фоне: ./update_daily.sh -d или ./update_daily.sh --background
if [ "$1" = "-d" ] || [ "$1" = "--background" ] || [ "$1" = "bg" ]; then
    LOG_FILE="update_daily.log"
    echo "Запуск update_daily.sh в фоне..."
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

URL="${FLIBUSTA_DAILY_URL:-https://flibusta.is/daily/}"
DEST_DIR="${BOOKS_DIR:-./Flibusta.Net}"
mkdir -p "$DEST_DIR"

echo "Checking daily updates from $URL to $DEST_DIR..."
curl -s "$URL" > page.html || true

if [ -s page.html ]; then
    grep -Eo 'href="f\.(fb2|n)\.[0-9\-]+\.zip"' page.html | sed 's/href="//;s/"//' > links.txt
    while IFS= read -r file; do
        if [ -n "$file" ]; then
            echo "Downloading $file..."
            wget -c -P "$DEST_DIR" "$URL$file" || true
        fi
    done < links.txt
    rm -f page.html links.txt
    echo "Daily download finished."
else
    echo "Failed to fetch page from $URL (check internet connection, mirror or proxy)."
    rm -f page.html
fi