#!/bin/sh
set -e

BASE_URL="${FLIBUSTA_SQL_URL:-https://flibusta.is/sql/}"
DEST_DIR="${SQL_DIR:-./FlibustaSQL}"

mkdir -p "$DEST_DIR"

echo "Downloading SQL dumps from $BASE_URL to $DEST_DIR..."

FILES="lib.libavtor.sql.gz lib.libtranslator.sql.gz lib.libavtorname.sql.gz lib.libbook.sql.gz lib.libfilename.sql.gz lib.libgenre.sql.gz lib.libgenrelist.sql.gz lib.libjoinedbooks.sql.gz lib.librate.sql.gz lib.librecs.sql.gz lib.libseqname.sql.gz lib.libseq.sql.gz lib.reviews.sql.gz lib.b.annotations.sql.gz lib.a.annotations.sql.gz lib.b.annotations_pics.sql.gz lib.a.annotations_pics.sql.gz"

for file in $FILES; do
    echo "Fetching $file..."
    wget --directory-prefix="$DEST_DIR" -c -nc "${BASE_URL}${file}" || true
done

echo "Starting database import in docker container..."
if docker compose version >/dev/null 2>&1; then
    docker compose exec php-fpm /application/tools/app_import_sql.sh
elif docker-compose version >/dev/null 2>&1; then
    docker-compose exec php-fpm /application/tools/app_import_sql.sh
else
    CONTAINER_ID=$(docker ps -q --filter "name=flibusta_php" || docker ps -q --filter "ancestor=flibusta_php-fpm")
    if [ -n "$CONTAINER_ID" ]; then
        docker exec "$CONTAINER_ID" /application/tools/app_import_sql.sh
    else
        echo "Error: Could not find flibusta php container"
        exit 1
    fi
fi

