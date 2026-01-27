# Telegram Bot Service

## Описание

Создан собственный сервис `TelegramBotService` для работы с Telegram Bot API. Сервис полностью совместим с Laravel 10 и не требует дополнительных зависимостей.

## Использование

### Базовое использование

```php
use App\Services\TelegramBotService;

$telegramService = new TelegramBotService();

// Отправить сообщение
$result = $telegramService->sendMessage(
    $token,
    $chatId,
    'Привет! Это тестовое сообщение'
);

// Проверить токен
$isValid = $telegramService->validateToken($token);

// Получить информацию о боте
$botInfo = $telegramService->getMe($token);
```

### Доступные методы

#### `sendMessage($token, $chatId, $text, $options = [])`
Отправляет текстовое сообщение в Telegram.

**Параметры:**
- `$token` - токен бота
- `$chatId` - ID чата или username (например, `@username`)
- `$text` - текст сообщения
- `$options` - дополнительные опции (parse_mode, reply_markup и т.д.)

**Возвращает:**
```php
[
    'success' => true,
    'message_id' => 123
]
// или
[
    'success' => false,
    'error' => 'Error message'
]
```

#### `getMe($token)`
Получает информацию о боте.

**Возвращает:**
```php
[
    'success' => true,
    'bot' => [
        'id' => 123456789,
        'is_bot' => true,
        'first_name' => 'Bot Name',
        'username' => 'bot_username'
    ]
]
```

#### `validateToken($token)`
Проверяет валидность токена бота.

**Возвращает:** `bool`

#### `setWebhook($token, $url, $options = [])`
Устанавливает webhook для бота.

#### `deleteWebhook($token)`
Удаляет webhook для бота.

#### `getWebhookInfo($token)`
Получает информацию о текущем webhook.

#### `sendDocument($token, $chatId, $document, $caption = null)`
Отправляет документ в Telegram.

## API Endpoints

### Проверка токена
```
POST /api/admin/shops/{shop}/validate-bot-token
```

### Получение информации о боте
```
GET /api/admin/shops/{shop}/bot-info
```

### Отправка тестового сообщения
```
POST /api/admin/shops/{shop}/send-test-message
Body: {
    "chat_id": "123456789",
    "message": "Тестовое сообщение"
}
```

## Интеграция в форму магазина

В форме создания/редактирования магазина добавлена возможность:
- Проверки валидности токена бота
- Просмотра информации о боте (имя, username)
- Визуальной индикации статуса токена (валиден/невалиден)

## Примеры использования в коде

### Отправка уведомления при создании заказа

```php
use App\Services\TelegramBotService;

$shop = Shop::find($shopId);
if ($shop->telegram_bot_token) {
    $telegramService = new TelegramBotService();
    $telegramService->sendMessage(
        $shop->telegram_bot_token,
        $adminChatId,
        "Новый заказ #{$orderId} в магазине {$shop->name}"
    );
}
```

### Проверка токена перед сохранением

```php
$telegramService = new TelegramBotService();
if (!$telegramService->validateToken($request->telegram_bot_token)) {
    return response()->json([
        'message' => 'Невалидный токен телеграм-бота'
    ], 422);
}
```

## Расширение функционала

Для добавления новых методов работы с Telegram Bot API:

1. Добавьте метод в `TelegramBotService`
2. Используйте стандартный HTTP клиент Laravel (`Http::`)
3. Обрабатывайте ошибки и логируйте их
4. Возвращайте единообразный формат ответа

## Документация Telegram Bot API

Полная документация доступна на: https://core.telegram.org/bots/api
