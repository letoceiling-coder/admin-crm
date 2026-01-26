# Интеграция подписки между CRM и ADMIN

## Обзор

CRM получает актуальную информацию о подписке из ADMIN через API endpoint.

## Процесс работы

### 1. Регистрация в CRM
- При регистрации пользователя в CRM отправляется заявка в ADMIN
- ADMIN создает `SubscriptionApplication` с `api_token` и `expires_at` (3 дня)
- Токен сохраняется в CRM в `settings` (ключи: `api_token`, `admin_api_token`)

### 2. Подтверждение заявки в ADMIN
- Администратор в ADMIN подтверждает заявку (`approve`)
- Создается `Subscriber` с `api_token` из заявки
- Статус заявки меняется на `approved`

### 3. Получение информации о подписке в CRM
- CRM запрашивает информацию через `/api/admin/subscription`
- Контроллер `SubscriptionController` в CRM:
  1. Запрашивает данные из ADMIN через `AdminApiService::getSubscriptionInfo()`
  2. Если успешно - возвращает данные из ADMIN
  3. Если ошибка - использует локальные настройки из `settings`

## API Endpoints

### ADMIN: GET /api/v1/subscription
**Параметры:**
- `domain` (опционально) - домен CRM
- `api_token` (опционально) - токен подписки

**Ответ:**
```json
{
  "success": true,
  "data": {
    "status": "active",
    "domain": "crm.neeklo.ru",
    "api_token": "полный_токен_или_частично_скрытый",
    "subscription_start": "2026-01-26",
    "subscription_end": "2027-01-26",
    "expires_at": "2027-01-26 00:00:00",
    "is_active": true,
    "plan": {
      "id": 1,
      "name": "standard",
      "cost": 1000.00,
      "is_active": true,
      "limits": {}
    }
  }
}
```

### CRM: GET /api/admin/subscription
**Требования:**
- Авторизация: `auth:sanctum`
- Доступ: `admin.access` middleware

**Ответ:**
```json
{
  "subscription": {
    "status": "active",
    "api_token": "токен_или_частично_скрытый",
    "expires_at": "2027-01-26 00:00:00",
    "subscription_start": "2026-01-26",
    "subscription_end": "2027-01-26",
    "domain": "crm.neeklo.ru",
    "is_active": true,
    "plan": {...},
    "application_id": 123
  }
}
```

## Настройка

### В CRM (.env):
```env
APP_CRM_URL=https://admin.neeklo.ru/api/v1
APP_DOMAIN=crm.neeklo.ru
```

### В ADMIN:
- Роут `/api/v1/subscription` доступен публично (с throttle)
- Поиск по `domain` или `api_token`
- При запросе с `api_token` возвращается полный токен
- При запросе только по `domain` токен частично скрыт

## Логика получения данных

1. **Приоритет 1:** Активный `Subscriber` (если есть)
   - Статус: `active`
   - Данные из таблицы `subscribers`

2. **Приоритет 2:** Заявка `SubscriptionApplication` (если нет подписчика)
   - Статус: `pending`, `approved`, `rejected`
   - Данные из таблицы `subscription_applications`

3. **Fallback:** Локальные настройки в CRM
   - Если не удалось получить из ADMIN
   - Используются значения из `settings`

## Обновление токена

При получении данных из ADMIN:
- Если получен полный токен (без `...`) - сохраняется в `settings`
- Обновляются: `expires_at`, `subscription_status`
- Токен используется для последующих запросов к ADMIN
