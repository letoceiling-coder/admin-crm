# Команды для проверки и настройки Deploy

## 1. Проверка на сервере (выполнить через SSH)

```bash
# Подключиться к серверу
ssh dsc23ytp@dragon

# Перейти в директорию проекта
cd ~/crm.neeklo.ru/public_html

# Проверить, что это git репозиторий
git status

# Проверить remote репозиторий
git remote -v

# Если remote не настроен, добавить:
git remote add origin https://github.com/letoceiling-coder/admin-crm.git

# Проверить текущую ветку
git branch

# Если нужно переключиться на main:
git checkout -b main
# или
git branch -M main

# Проверить PHP версию
php --version

# Проверить путь к PHP (может быть php8.1, php8.2, php8.3)
which php
php8.1 --version
php8.2 --version
php8.3 --version

# Проверить composer
composer --version

# Проверить права на директории
ls -la storage/
ls -la bootstrap/cache/

# Установить права (если нужно)
chmod -R 775 storage bootstrap/cache
chown -R dsc23ytp:dsc23ytp storage bootstrap/cache

# Проверить .env файл
cat .env | grep DEPLOY

# Проверить, что DEPLOY_TOKEN установлен
grep DEPLOY_TOKEN .env

# Проверить структуру проекта
ls -la

# Проверить наличие .htaccess
ls -la .htaccess
ls -la public/.htaccess

# Проверить git safe directory (если будут проблемы)
git config --global --add safe.directory ~/crm.neeklo.ru/public_html
```

## 2. Настройка на локальной машине

### Проверить .env файл локально:

```bash
cd c:\OSPanel\domains\ADMIN-CRM\crm

# Проверить наличие DEPLOY_SERVER_URL и DEPLOY_TOKEN
# Откройте .env файл и убедитесь, что есть:
# DEPLOY_SERVER_URL=https://crm.neeklo.ru
# DEPLOY_TOKEN=4dc714198d297556aa76904a976abbff1ab3707f4d4533eecbc3c037a62dae07
```

### Добавить в локальный .env (если нет):

```
DEPLOY_SERVER_URL=https://crm.neeklo.ru
DEPLOY_TOKEN=4dc714198d297556aa76904a976abbff1ab3707f4d4533eecbc3c037a62dae07
```

## 3. Проверка git на локальной машине

```bash
cd c:\OSPanel\domains\ADMIN-CRM\crm

# Проверить remote
git remote -v

# Должен быть:
# origin  https://github.com/letoceiling-coder/admin-crm.git (fetch)
# origin  https://github.com/letoceiling-coder/admin-crm.git (push)

# Проверить текущую ветку
git branch

# Проверить статус
git status
```

## 4. Тестирование deploy команды

### Сначала тест в режиме dry-run (без реальных изменений):

```bash
cd c:\OSPanel\domains\ADMIN-CRM\crm

# Тест без выполнения (dry-run)
php artisan deploy --dry-run

# Тест с пропуском сборки
php artisan deploy --dry-run --skip-build
```

### Если все ок, выполнить реальный deploy:

```bash
# Полный deploy
php artisan deploy

# Deploy с кастомным сообщением
php artisan deploy --message="Initial deploy to production"

# Deploy без сборки фронтенда (если уже собрано)
php artisan deploy --skip-build

# Deploy с выполнением seeders
php artisan deploy --with-seed

# Deploy с отключением проверки SSL (если проблемы с сертификатом)
php artisan deploy --insecure
```

## 5. Проверка после deploy

### На сервере проверить логи:

```bash
ssh dsc23ytp@dragon
cd ~/crm.neeklo.ru/public_html

# Проверить логи Laravel
tail -f storage/logs/laravel.log

# Проверить последние логи
tail -n 100 storage/logs/laravel.log | grep -i deploy
```

### Проверить, что код обновился:

```bash
# На сервере
cd ~/crm.neeklo.ru/public_html
git log -1
git status
```

## 6. Возможные проблемы и решения

### Проблема: "DEPLOY_TOKEN не настроен"
**Решение:** Проверить .env на сервере, убедиться что DEPLOY_TOKEN есть

### Проблема: "Git safe directory"
**Решение:** Выполнить на сервере:
```bash
git config --global --add safe.directory ~/crm.neeklo.ru/public_html
```

### Проблема: "PHP не найден"
**Решение:** Указать PHP_PATH в .env на сервере:
```
PHP_PATH=/usr/bin/php8.2
# или
PHP_PATH=/usr/bin/php8.3
```

### Проблема: "Composer не найден"
**Решение:** Указать COMPOSER_PATH в .env на сервере или установить composer в bin/composer

### Проблема: "SSL certificate verify failed"
**Решение:** Использовать флаг --insecure при deploy (только для тестирования)

### Проблема: "Permission denied"
**Решение:** Проверить права на storage и bootstrap/cache:
```bash
chmod -R 775 storage bootstrap/cache
chown -R dsc23ytp:dsc23ytp storage bootstrap/cache
```

## 7. Полная последовательность первого deploy

```bash
# 1. На сервере - инициализация git (если еще не сделано)
ssh dsc23ytp@dragon
cd ~/crm.neeklo.ru/public_html
git init
git remote add origin https://github.com/letoceiling-coder/admin-crm.git
git fetch origin
git checkout -b main
git reset --hard origin/main

# 2. На сервере - настройка прав
chmod -R 775 storage bootstrap/cache
chown -R dsc23ytp:dsc23ytp storage bootstrap/cache

# 3. На сервере - composer install
composer install --no-dev --optimize-autoloader

# 4. На сервере - миграции
php artisan migrate --force

# 5. На сервере - очистка кешей
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 6. На локальной машине - проверить .env
# Убедиться что есть DEPLOY_SERVER_URL и DEPLOY_TOKEN

# 7. На локальной машине - выполнить deploy
cd c:\OSPanel\domains\ADMIN-CRM\crm
php artisan deploy
```
