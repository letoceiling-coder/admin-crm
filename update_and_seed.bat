@echo off
REM Скрипт для автоматического обновления и запуска seeder (Windows)
REM Использование: update_and_seed.bat

echo ===============================================
echo Обновление товаров с питательными веществами
echo ===============================================
echo.

REM Проверяем наличие JSON файла
if not exist "storage\pirogi_data.json" (
    echo [ERROR] Файл storage\pirogi_data.json не найден!
    pause
    exit /b 1
)

echo [INFO] Проверка данных...
php update_products_with_nutrition.php
echo.

echo.
echo [INFO] Выполнение миграций...
php artisan migrate --force

if errorlevel 1 (
    echo [ERROR] Ошибка при выполнении миграций!
    pause
    exit /b 1
)

echo [OK] Миграции выполнены
echo.

echo [INFO] Запуск seeder...
php artisan db:seed --class=PirogiSeeder

if errorlevel 1 (
    echo [ERROR] Ошибка при выполнении seeder!
    pause
    exit /b 1
)

echo.
echo ===============================================
echo [OK] Готово! Товары обновлены.
echo ===============================================
pause
