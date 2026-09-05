#!/bin/sh
set -e

. /application/tools/dbinit.sh

# Ожидание готовности PostgreSQL
echo "Проверка доступности базы данных PostgreSQL ($FLIBUSTA_DBHOST)..."
MAX_TRIES=30
COUNT=0
until $SQL_CMD -c '\q' 2>/dev/null || [ $COUNT -ge $MAX_TRIES ]; do
    sleep 2
    COUNT=$((COUNT + 1))
done

if [ $COUNT -ge $MAX_TRIES ]; then
    echo "Предупреждение: Не удалось подключиться к PostgreSQL за $MAX_TRIES попыток."
else
    echo "База данных PostgreSQL готова к работе."
fi

# Автоматический фоновый импорт дампа при первом запуске (если включено в .env)
if [ "$AUTO_IMPORT_ON_START" = "true" ] || [ "$AUTO_SYNC_ON_START" = "true" ]; then
    BOOK_COUNT=$($SQL_CMD -t -A -c "SELECT COUNT(*) FROM libbook;" 2>/dev/null || echo "0")
    if [ "$BOOK_COUNT" = "0" ] || [ -z "$BOOK_COUNT" ]; then
        echo "База данных пуста (0 книг). AUTO_IMPORT_ON_START=true: запуск автоматического скачивания и импорта в фоне..."
        /application/tools/docker_sync_sql.sh > /application/sql/sync.log 2>&1 &
    else
        echo "База данных уже содержит $BOOK_COUNT книг. Пропуск первичного импорта."
    fi
fi

# Запуск PHP-FPM
exec docker-php-entrypoint php-fpm