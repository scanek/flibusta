# PowerShell Installer for Flibusta Mirror
$ErrorActionPreference = "Stop"

$RepoUrl = "https://github.com/scanek/flibusta.git"
$DefaultDir = "flibusta"

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host "   Установка локального зеркала Flibusta из Git     " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan
Write-Host ""

# Проверяем, в репозитории ли мы
if ((Test-Path "docker-compose.yml") -and (Test-Path "application")) {
    Write-Host "Запуск из текущей папки репозитория." -ForegroundColor Green
} else {
    Write-Host "Клонирование репозитория из $RepoUrl..." -ForegroundColor Yellow
    if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
        Write-Error "Ошибка: git не установлен! Установите Git for Windows."
    }
    if (Test-Path $DefaultDir) {
        Set-Location $DefaultDir
        git pull
    } else {
        git clone $RepoUrl $DefaultDir
        Set-Location $DefaultDir
    }
}

Write-Host "`n--> Проверка зависимостей (Docker)..." -ForegroundColor Blue
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Error "Ошибка: Docker не найден! Установите Docker Desktop для Windows."
}

# Проверка Compose
$ComposeCmd = "docker compose"
try {
    & docker compose version | Out-Null
} catch {
    if (Get-Command docker-compose -ErrorAction SilentlyContinue) {
        $ComposeCmd = "docker-compose"
    } else {
        Write-Error "Ошибка: Docker Compose не найден!"
    }
}

Write-Host "`n--> Настройка параметров (.env)..." -ForegroundColor Blue
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
}

$DefaultBooks = "./Flibusta.Net"
$DefaultWebPort = "27100"
$DefaultPgPort = "27101"

$InputBooks = Read-Host "Путь к папке с книгами (*.zip) [по умолчанию: $DefaultBooks]"
$BooksDir = if ([string]::IsNullOrWhiteSpace($InputBooks)) { $DefaultBooks } else { $InputBooks }

$InputPort = Read-Host "Внешний веб-порт [по умолчанию: $DefaultWebPort]"
$WebPort = if ([string]::IsNullOrWhiteSpace($InputPort)) { $DefaultWebPort } else { $InputPort }

$InputPg = Read-Host "Внешний порт PostgreSQL [по умолчанию: $DefaultPgPort]"
$PgPort = if ([string]::IsNullOrWhiteSpace($InputPg)) { $DefaultPgPort } else { $InputPg }

$InputPwd = Read-Host "Пароль для раздела 'Сервис' (Enter без пароля)"
$ServicePwd = if ([string]::IsNullOrWhiteSpace($InputPwd)) { "" } else { $InputPwd }

$EnvContent = @"
COMPOSE_PROJECT_NAME=flibusta
WEB_PORT=$WebPort
PG_PORT=$PgPort
POSTGRES_USER=flibusta
POSTGRES_PASSWORD=flibusta
POSTGRES_DB=flibusta
SERVICE_PASSWORD=$ServicePwd
BOOKS_DIR=$BooksDir
SQL_DIR=./FlibustaSQL
CACHE_DIR=./cache
FLIBUSTA_SQL_URL=https://flibusta.is/sql/
FLIBUSTA_DAILY_URL=https://flibusta.is/daily/
"@
Set-Content -Path ".env" -Value $EnvContent -Encoding utf8
Write-Host "Настройки сохранены в .env" -ForegroundColor Green

Write-Host "`n--> Создание каталогов..." -ForegroundColor Blue
New-Item -ItemType Directory -Force -Path $BooksDir, "./FlibustaSQL", "./cache/covers", "./cache/authors", "./cache/tmp" | Out-Null

Write-Host "`n--> Сборка и запуск контейнеров Docker..." -ForegroundColor Blue
if ($ComposeCmd -eq "docker compose") {
    docker compose build
    docker compose up -d
} else {
    docker-compose build
    docker-compose up -d
}

Write-Host "`n====================================================" -ForegroundColor Green
Write-Host "   Flibusta успешно установлена и запущена!         " -ForegroundColor Green
Write-Host "====================================================" -ForegroundColor Green
Write-Host "Веб-интерфейс:   http://localhost:$WebPort/" -ForegroundColor Cyan
Write-Host "OPDS-каталог:    http://localhost:$WebPort/opds/" -ForegroundColor Cyan
Write-Host "Папка с книгами: $BooksDir" -ForegroundColor Yellow
