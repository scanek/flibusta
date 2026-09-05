#!/usr/bin/env bash
set -e

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

REPO_URL="https://github.com/scanek/flibusta.git"
DEFAULT_DIR="flibusta"

echo -e "${CYAN}====================================================${NC}"
echo -e "${CYAN}   Установка локального зеркала Flibusta из Git     ${NC}"
echo -e "${CYAN}====================================================${NC}\n"

# Если запуск через curl | bash, перенаправляем ввод с терминала
if [ ! -t 0 ] && [ -e /dev/tty ]; then
    exec < /dev/tty
fi

# Проверка, находимся ли мы уже внутри склонированного репозитория
if [ -f "docker-compose.yml" ] && [ -d "application" ]; then
    PROJECT_DIR="$(pwd)"
    echo -e "${GREEN}Установка запускается из текущей папки репозитория: ${PROJECT_DIR}${NC}"
else
    echo -e "${YELLOW}Клонирование репозитория из ${REPO_URL}...${NC}"
    if ! command -v git >/dev/null 2>&1; then
        echo -e "${RED}Ошибка: git не установлен! Установите git и повторите попытку.${NC}"
        exit 1
    fi
    TARGET_DIR="${1:-$DEFAULT_DIR}"
    if [ -d "$TARGET_DIR" ]; then
        echo -e "${YELLOW}Папка '$TARGET_DIR' уже существует. Переходим в неё...${NC}"
        cd "$TARGET_DIR"
        git pull || true
    else
        git clone "$REPO_URL" "$TARGET_DIR"
        cd "$TARGET_DIR"
    fi
    PROJECT_DIR="$(pwd)"
fi

# Очистка возможных CRLF окончаний строк
sed -i 's/\r$//' *.sh application/tools/*.sh application/tools/app_topg .env* 2>/dev/null || true

echo -e "\n${BLUE}--> Проверка зависимостей (Docker)...${NC}"
if ! command -v docker >/dev/null 2>&1; then
    echo -e "${RED}Ошибка: Docker не найден! Установите Docker (https://docs.docker.com/get-docker/) и повторите запуск.${NC}"
    exit 1
fi

if ! docker info >/dev/null 2>&1; then
    echo -e "${RED}Ошибка: Docker демон не запущен или у текущего пользователя нет прав!${NC}"
    echo -e "Попробуйте запустить Docker службу или выполнить команду с sudo / добавить пользователя в группу docker."
    exit 1
fi

# Определение команды Docker Compose (v2 или standalone)
if docker compose version >/dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    DOCKER_COMPOSE="docker-compose"
else
    echo -e "${RED}Ошибка: Docker Compose не найден! Установите docker-compose-plugin.${NC}"
    exit 1
fi
echo -e "${GREEN}Используется Compose: $($DOCKER_COMPOSE version)${NC}"

echo -e "\n${BLUE}--> Настройка параметров (.env)...${NC}"

# Если .env уже есть, считываем существующие значения
if [ -f ".env" ]; then
    echo -e "${YELLOW}Файл .env уже существует.${NC}"
    source .env 2>/dev/null || true
else
    cp .env.example .env
fi

DEFAULT_BOOKS_DIR="${BOOKS_DIR:-./Flibusta.Net}"
DEFAULT_WEB_PORT="${WEB_PORT:-27100}"
DEFAULT_PG_PORT="${PG_PORT:-27101}"

read -r -p "Укажите путь к папке с книгами (архивы *.zip) [по умолчанию: $DEFAULT_BOOKS_DIR]: " INPUT_BOOKS_DIR
BOOKS_DIR="${INPUT_BOOKS_DIR:-$DEFAULT_BOOKS_DIR}"

read -r -p "Внешний порт для веб-интерфейса и OPDS [по умолчанию: $DEFAULT_WEB_PORT]: " INPUT_WEB_PORT
WEB_PORT="${INPUT_WEB_PORT:-$DEFAULT_WEB_PORT}"

read -r -p "Внешний порт PostgreSQL [по умолчанию: $DEFAULT_PG_PORT]: " INPUT_PG_PORT
PG_PORT="${INPUT_PG_PORT:-$DEFAULT_PG_PORT}"

read -r -p "Пароль для раздела 'Сервис' (Enter для доступа без пароля): " INPUT_SERVICE_PASSWORD
SERVICE_PASSWORD="${INPUT_SERVICE_PASSWORD:-$SERVICE_PASSWORD}"

# Запись в .env
cat <<EOF > .env
COMPOSE_PROJECT_NAME=flibusta
WEB_PORT=$WEB_PORT
PG_PORT=$PG_PORT
POSTGRES_USER=${POSTGRES_USER:-flibusta}
POSTGRES_PASSWORD=${POSTGRES_PASSWORD:-flibusta}
POSTGRES_DB=${POSTGRES_DB:-flibusta}
SERVICE_PASSWORD=$SERVICE_PASSWORD
BOOKS_DIR=$BOOKS_DIR
SQL_DIR=${SQL_DIR:-./FlibustaSQL}
CACHE_DIR=${CACHE_DIR:-./cache}
FLIBUSTA_SQL_URL=${FLIBUSTA_SQL_URL:-https://flibusta.is/sql/}
FLIBUSTA_DAILY_URL=${FLIBUSTA_DAILY_URL:-https://flibusta.is/daily/}
EOF

echo -e "${GREEN}Настройки сохранены в .env${NC}"

echo -e "\n${BLUE}--> Создание рабочих каталогов и установка прав...${NC}"
mkdir -p "$BOOKS_DIR"
mkdir -p "${SQL_DIR:-./FlibustaSQL}"
mkdir -p "${CACHE_DIR:-./cache}/covers"
mkdir -p "${CACHE_DIR:-./cache}/authors"
mkdir -p "${CACHE_DIR:-./cache}/tmp"
touch "${SQL_DIR:-./FlibustaSQL}/sync.log" "${SQL_DIR:-./FlibustaSQL}/status" 2>/dev/null || true

chmod +x *.sh 2>/dev/null || true
chmod +x application/tools/*.sh 2>/dev/null || true
chmod +x application/tools/app_topg 2>/dev/null || true
sed -i 's/\r$//' *.sh application/tools/*.sh application/tools/app_topg .env* 2>/dev/null || true

# Выставляем полные права на запись для кэша и каталога SQL
chmod -R 777 "${CACHE_DIR:-./cache}" "${SQL_DIR:-./FlibustaSQL}" 2>/dev/null || true
if [ "$(id -u)" -eq 0 ]; then
    chown -R 82:82 "${CACHE_DIR:-./cache}" "${SQL_DIR:-./FlibustaSQL}" 2>/dev/null || true
fi

echo -e "\n${BLUE}--> Сборка и запуск контейнеров Docker...${NC}"
$DOCKER_COMPOSE build
$DOCKER_COMPOSE up -d

echo -e "\n${GREEN}====================================================${NC}"
echo -e "${GREEN}   Flibusta успешно установлена и запущена!         ${NC}"
echo -e "${GREEN}====================================================${NC}"
echo -e "Веб-интерфейс:      ${CYAN}http://localhost:${WEB_PORT}/${NC}"
echo -e "OPDS-каталог:       ${CYAN}http://localhost:${WEB_PORT}/opds/${NC}"
echo -e "Папка с книгами:    ${YELLOW}${BOOKS_DIR}${NC}"
echo -e "Папка для SQL-дампа:${YELLOW}${SQL_DIR:-./FlibustaSQL}${NC}"
echo -e "\nСледующие шаги:"
echo -e "1. Скачать или скопировать дамп базы в папку '${SQL_DIR:-./FlibustaSQL}' (или запустить: ./getsql.sh)"
echo -e "2. Разместить архивы книг в папку '${BOOKS_DIR}' (или запустить: ./update_daily.sh)"
echo -e "3. В веб-интерфейсе перейти в меню 'Сервис' и нажать 'Обновить базу'\n"