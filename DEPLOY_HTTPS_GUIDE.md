# Руководство по Deploy через HTTPS

## Быстрый старт

### 1. Проверка доступности сайта

Откройте в браузере:
```
https://crm.neeklo.ru
```

Сайт должен открываться без ошибок.

### 2. Проверка deploy endpoint

Откройте в браузере:
```
https://crm.neeklo.ru/api/deploy
```

Должен вернуться JSON с ошибкой авторизации (это нормально - значит endpoint работает):
```json
{
  "success": false,
  "message": "Неверный секретный ключ"
}
```

### 3. Настройка локального .env

Откройте файл `c:\OSPanel\domains\ADMIN-CRM\crm\.env` и добавьте:

```env
DEPLOY_SERVER_URL=https://crm.neeklo.ru
DEPLOY_TOKEN=4dc714198d297556aa76904a976abbff1ab3707f4d4533eecbc3c037a62dae07
```

### 4. Тестирование через PowerShell

Запустите скрипт:
```powershell
cd c:\OSPanel\domains\ADMIN-CRM\crm
.\test-deploy.ps1
```

Скрипт проверит:
- Доступность сайта
- Защиту endpoint (с неверным токеном)
- Выполнит реальный deploy (с подтверждением)

### 5. Выполнение deploy через artisan

```powershell
cd c:\OSPanel\domains\ADMIN-CRM\crm

# Тест без реальных изменений
php artisan deploy --dry-run

# Реальный deploy
php artisan deploy

# Deploy с кастомным сообщением
php artisan deploy --message="Описание изменений"
```

## Проверка через веб-интерфейс хостинга

### Через файловый менеджер

1. Войдите в панель управления хостингом
2. Откройте "Файловый менеджер"
3. Перейдите в `~/crm.neeklo.ru/public_html`
4. Проверьте наличие:
   - `.env` файл (с DEPLOY_TOKEN)
   - `.git` директория
   - `storage/` директория (права 775)
   - `bootstrap/cache/` директория (права 775)

### Через Terminal в панели управления

Если в панели управления есть Terminal/SSH Access:

```bash
cd ~/crm.neeklo.ru/public_html

# Проверка git
git status
git remote -v

# Проверка .env
grep DEPLOY_TOKEN .env

# Проверка прав
ls -la storage/
```

## Тестирование через curl (если установлен)

### Тест с неверным токеном
```bash
curl -X POST https://crm.neeklo.ru/api/deploy \
  -H "X-Deploy-Token: wrong-token" \
  -H "Content-Type: application/json"
```

Ожидаемый результат: HTTP 403

### Тест с правильным токеном
```bash
curl -X POST https://crm.neeklo.ru/api/deploy \
  -H "X-Deploy-Token: 4dc714198d297556aa76904a976abbff1ab3707f4d4533eecbc3c037a62dae07" \
  -H "Content-Type: application/json" \
  -d '{"branch":"main","run_seeders":false}'
```

## Проверка после deploy

1. Откройте сайт: https://crm.neeklo.ru
2. Убедитесь, что нет ошибок
3. Через файловый менеджер проверьте логи:
   - `storage/logs/laravel.log`
   - Ищите записи с "deploy" или "Деплой"

## Возможные проблемы

### Проблема: "SSL certificate verify failed"
**Решение:** Используйте флаг `--insecure`:
```bash
php artisan deploy --insecure
```

### Проблема: "Неверный секретный ключ"
**Решение:** 
- Проверьте, что DEPLOY_TOKEN в локальном .env совпадает с токеном на сервере
- Убедитесь, что токен скопирован полностью (без пробелов)

### Проблема: "Connection timeout"
**Решение:**
- Проверьте доступность https://crm.neeklo.ru
- Проверьте настройки файрвола
- Увеличьте таймаут в команде (по умолчанию 5 минут)

### Проблема: Endpoint не отвечает
**Решение:**
- Проверьте, что файл `.htaccess` создан в корне проекта
- Проверьте, что route зарегистрирован: `routes/api.php`
- Проверьте логи на сервере: `storage/logs/laravel.log`

## Первый deploy (если проект еще не в git на сервере)

Для первого деплоя нужен доступ к серверу через Terminal в панели управления:

```bash
cd ~/crm.neeklo.ru/public_html
git init
git remote add origin https://github.com/letoceiling-coder/admin-crm.git
git fetch origin
git checkout -b main
git reset --hard origin/main
chmod -R 775 storage bootstrap/cache
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
```

Затем на локальной машине:
```bash
cd c:\OSPanel\domains\ADMIN-CRM\crm
php artisan deploy
```
