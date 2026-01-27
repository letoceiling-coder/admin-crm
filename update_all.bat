@echo off
REM Единая команда для обновления всего
REM Использование: update_all.bat

echo ===============================================
echo Обновление товаров - все в одной команде
echo ===============================================
echo.

echo [1/3] Проверка данных...
php update_products_with_nutrition.php
echo.

echo [2/3] Выполнение миграций...
php artisan migrate --force
if errorlevel 1 (
    echo [ERROR] Ошибка при выполнении миграций!
    pause
    exit /b 1
)
echo [OK] Миграции выполнены
echo.

echo [3/3] Запуск seeder...
php artisan db:seed --class=PirogiSeeder
if errorlevel 1 (
    echo [ERROR] Ошибка при выполнении seeder!
    pause
    exit /b 1
)
echo [OK] Seeder выполнен
echo.

echo ===============================================
echo [OK] Готово! Все обновлено.
echo ===============================================
echo.
echo Проверьте: /admin/products - любой товар
echo.
pause
