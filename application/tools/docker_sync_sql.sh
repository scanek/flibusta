#!/bin/sh
set -e

. /application/tools/dbinit.sh

BASE_URL="${FLIBUSTA_SQL_URL:-https://flibusta.is/sql/}"
DEST_DIR="/application/sql"

mkdir -p "$DEST_DIR"
mkdir -p "$DEST_DIR/psql"
mkdir -p /application/cache/authors
mkdir -p /application/cache/covers
mkdir -p /application/cache/tmp

echo "Запуск синхронизации SQL с $BASE_URL..." > /application/sql/status

FILES="lib.libavtor.sql.gz lib.libtranslator.sql.gz lib.libavtorname.sql.gz lib.libbook.sql.gz lib.libfilename.sql.gz lib.libgenre.sql.gz lib.libgenrelist.sql.gz lib.libjoinedbooks.sql.gz lib.librate.sql.gz lib.librecs.sql.gz lib.libseqname.sql.gz lib.libseq.sql.gz lib.reviews.sql.gz lib.b.annotations.sql.gz lib.a.annotations.sql.gz lib.b.annotations_pics.sql.gz lib.a.annotations_pics.sql.gz"

for file in $FILES; do
    echo "Скачивание таблицы $file..." > /application/sql/status
    wget --directory-prefix="$DEST_DIR" -c -nc "${BASE_URL}${file}" || true
done

# Скачивание обложек и фото авторов при их отсутствии
if [ ! -f "/application/cache/lib.a.attached.zip" ]; then
    echo "Скачивание архива фото авторов lib.a.attached.zip..." > /application/sql/status
    wget --directory-prefix="/application/cache" -c -nc "${BASE_URL}lib.a.attached.zip" || true
fi
if [ ! -f "/application/cache/lib.b.attached.zip" ]; then
    echo "Скачивание архива обложек lib.b.attached.zip..." > /application/sql/status
    wget --directory-prefix="/application/cache" -c -nc "${BASE_URL}lib.b.attached.zip" || true
fi

echo "Скачивание завершено. Переход к распаковке и импорту..." > /application/sql/status
/application/tools/app_import_sql.sh

echo "" > /application/sql/status
echo "Синхронизация базы данных успешно завершена."