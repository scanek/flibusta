#!/bin/sh
set -e

URL="${FLIBUSTA_DAILY_URL:-https://flibusta.is/daily/}"
DEST_DIR="${BOOKS_DIR:-./Flibusta.Net}"
mkdir -p "$DEST_DIR"

echo "Checking daily updates from $URL..."
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
