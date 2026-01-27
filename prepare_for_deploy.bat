@echo off
REM Подготовка к деплою - сбор данных и проверка
REM Использование: prepare_for_deploy.bat

echo ===============================================
echo Подготовка к деплою
echo ===============================================
echo.

echo [1/3] Проверка Node.js...
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Node.js не установлен!
    echo Установите Node.js: https://nodejs.org/
    pause
    exit /b 1
)
echo [OK] Node.js найден
echo.

echo [2/3] Установка зависимостей...
if not exist "node_modules" (
    call npm install
    if errorlevel 1 (
        echo [ERROR] Ошибка установки зависимостей
        pause
        exit /b 1
    )
) else (
    echo [OK] Зависимости уже установлены
)
echo.

echo [3/3] Сбор данных о питательных веществах...
call npm run collect
if errorlevel 1 (
    echo [ERROR] Ошибка сбора данных
    pause
    exit /b 1
)
echo.

echo ===============================================
echo [OK] Готово к деплою!
echo ===============================================
echo.
echo Файл storage/pirogi_data.json обновлен
echo Загрузите его на сервер вместе с остальными файлами
echo.
echo На сервере выполните:
echo php artisan db:seed --class=PirogiSeeder
echo.
pause
