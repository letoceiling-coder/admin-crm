#!/bin/bash
# Скрипт для обновления проекта на сервере из git

echo "========================================"
echo "  ОБНОВЛЕНИЕ ПРОЕКТА ИЗ GIT"
echo "========================================"
echo ""

# Переход в директорию проекта
cd ~/crm.neeklo.ru/public_html || {
    echo "ОШИБКА: Не удалось перейти в директорию проекта"
    exit 1
}

echo "📁 Текущая директория: $(pwd)"
echo ""

# Проверка git репозитория
if [ ! -d ".git" ]; then
    echo "⚠️  Git репозиторий не инициализирован"
    echo "🔧 Инициализация git..."
    git init
    git remote add origin https://github.com/letoceiling-coder/admin-crm.git
fi

# Проверка remote
echo "🔍 Проверка remote репозитория..."
if ! git remote | grep -q origin; then
    echo "🔧 Добавление remote origin..."
    git remote add origin https://github.com/letoceiling-coder/admin-crm.git
fi

# Сохранение локальных изменений
echo "💾 Сохранение локальных изменений (если есть)..."
git stash save "Backup before update $(date +%Y-%m-%d_%H-%M-%S)" 2>/dev/null || true

# Получение обновлений
echo "📥 Получение обновлений из репозитория..."
git fetch origin main || {
    echo "ОШИБКА: Не удалось получить обновления"
    exit 1
}

# Обновление файлов
echo "🔄 Обновление файлов..."
git reset --hard origin/main || {
    echo "ОШИБКА: Не удалось обновить файлы"
    exit 1
}

# Проверка текущего коммита
echo ""
echo "✅ Файлы обновлены!"
echo "📦 Текущий коммит: $(git rev-parse --short HEAD)"
echo ""

# Обновление зависимостей
echo "📦 Обновление зависимостей Composer..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction || {
        echo "⚠️  Предупреждение: Ошибка при composer install"
    }
else
    echo "⚠️  Composer не найден, пропуск установки зависимостей"
fi

# Определение PHP пути
PHP_PATH="php"
if command -v php8.3 &> /dev/null; then
    PHP_PATH="php8.3"
elif command -v php8.2 &> /dev/null; then
    PHP_PATH="php8.2"
elif command -v php8.1 &> /dev/null; then
    PHP_PATH="php8.1"
fi

echo "🔧 Используется PHP: $PHP_PATH"
echo ""

# Миграции
echo "🗄️  Выполнение миграций..."
$PHP_PATH artisan migrate --force || {
    echo "⚠️  Предупреждение: Ошибка при выполнении миграций"
}

# Очистка кешей
echo "🧹 Очистка кешей..."
$PHP_PATH artisan config:clear
$PHP_PATH artisan cache:clear
$PHP_PATH artisan route:clear
$PHP_PATH artisan view:clear

# Оптимизация
echo "⚡ Оптимизация..."
$PHP_PATH artisan config:cache
$PHP_PATH artisan route:cache
$PHP_PATH artisan view:cache

echo ""
echo "========================================"
echo "  ОБНОВЛЕНИЕ ЗАВЕРШЕНО УСПЕШНО!"
echo "========================================"
echo ""
echo "📋 Информация:"
echo "   - Коммит: $(git rev-parse --short HEAD)"
echo "   - Ветка: $(git rev-parse --abbrev-ref HEAD)"
echo "   - Дата: $(date)"
echo ""
