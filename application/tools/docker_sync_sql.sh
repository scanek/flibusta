#!/bin/sh
set -e

. /application/tools/dbinit.sh

BASE_URL="${FLIBUSTA_SQL_URL:-https://flibusta.is/sql/}"
DEST_DIR="/application/sql"
CACHE_DIR="/application/cache"
PID_FILE="/application/sql/sync.pid"

mkdir -p "$DEST_DIR/psql" "$CACHE_DIR/authors" "$CACHE_DIR/covers" "$CACHE_DIR/tmp"

# Запись PID текущего процесса
echo $$ > "$PID_FILE" 2>/dev/null || true
cleanup() {
    rm -f "$PID_FILE"
}
trap cleanup EXIT INT TERM

log_status() {
    echo "$1"
    echo "$1" > /application/sql/status 2>/dev/null || true
}

download_file() {
    file="$1"
    dest="$2"
    url="${BASE_URL}${file}"
    target="${dest}/${file}"

    # Если файл уже скачан и не пустой, пропускаем
    if [ -s "$target" ]; then
        echo "Файл $file уже существует, пропуск загрузки."
        return 0
    fi

    log_status "Скачивание $file..."
    echo "Загрузка $url -> $target"

    if command -v curl >/dev/null 2>&1; then
        curl -f -L -C - --retry 3 -o "$target" "$url" || curl -f -L --retry 3 -o "$target" "$url"
    elif command -v wget >/dev/null 2>&1; then
        wget -c -O "$target" "$url" || wget -O "$target" "$url"
    elif command -v python3 >/dev/null 2>&1; then
        python3 -c "import urllib.request; urllib.request.urlretrieve('$url', '$target')"
    else
        echo "Ошибка: не найден curl, wget или python3 для загрузки $url" >&2
        return 1
    fi
}

log_status "Запуск синхронизации SQL с $BASE_URL..."

FILES="lib.libavtor.sql.gz lib.libtranslator.sql.gz lib.libavtorname.sql.gz lib.libbook.sql.gz lib.libfilename.sql.gz lib.libgenre.sql.gz lib.libgenrelist.sql.gz lib.libjoinedbooks.sql.gz lib.librate.sql.gz lib.librecs.sql.gz lib.libseqname.sql.gz lib.libseq.sql.gz lib.reviews.sql.gz lib.b.annotations.sql.gz lib.a.annotations.sql.gz lib.b.annotations_pics.sql.gz lib.a.annotations_pics.sql.gz"

for file in $FILES; do
    download_file "$file" "$DEST_DIR" || echo "Предупреждение: Не удалось скачать $file, продолжаем..."
done

# Скачивание обложек и фото авторов при их отсутствии
if [ ! -s "$CACHE_DIR/lib.a.attached.zip" ]; then
    download_file "lib.a.attached.zip" "$CACHE_DIR" || true
fi
if [ ! -s "$CACHE_DIR/lib.b.attached.zip" ]; then
    download_file "lib.b.attached.zip" "$CACHE_DIR" || true
fi

log_status "Скачивание завершено. Переход к распаковке и импорту..."

/application/tools/app_import_sql.sh

log_status ""
echo "Синхронизация базы данных успешно завершена."