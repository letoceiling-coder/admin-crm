# План реализации настроек доставки

## Анализ проекта express

### Структура в express:
1. **Модель DeliverySetting** - singleton (одна запись)
2. **Таблица delivery_settings** - без user_id и shop_id
3. **DeliverySettingsController** - управление настройками
4. **DeliveryCalculationService** - расчет стоимости доставки
5. **Frontend компоненты:**
   - `DeliveryModeToggle` - переключение самовывоз/доставка
   - `DeliveryProgressIndicator` - прогрессбар до бесплатной доставки

### Что нужно изменить для текущего проекта:

## 1. База данных

### Миграция: `create_delivery_settings_table`
```php
- id
- user_id (nullable) - пользователь, создавший настройки
- shop_id (nullable) - магазин, к которому привязаны настройки
- yandex_geocoder_api_key (nullable)
- origin_address (nullable)
- origin_latitude (decimal 10,8)
- origin_longitude (decimal 11,8)
- default_city (string)
- free_delivery_threshold (decimal 10,2)
- delivery_zones (json) - массив зон
- is_enabled (boolean)
- min_delivery_order_total_rub (decimal 10,2)
- delivery_min_lead_hours (integer)
- timestamps
- unique(user_id, shop_id) - одна запись на комбинацию user+shop
```

## 2. Backend

### Модель DeliverySetting
- Связи: `belongsTo(User)`, `belongsTo(Shop)`
- Метод `getSettings($userId, $shopId)` - получить настройки для user/shop
- Метод `getDeliveryCost($distance, $cartTotal)` - расчет стоимости

### Контроллер DeliverySettingsController
- `index()` - GET /admin/settings/delivery - получить настройки для текущего user/shop
- `update()` - PUT /admin/settings/delivery - обновить настройки
- `getSettings()` - GET /api/v1/delivery-settings?shop_id=X - публичный для фронтенда
- `calculateCost()` - POST /api/v1/delivery/calculate-cost - расчет стоимости
- `getAddressSuggestions()` - POST /api/v1/delivery/address-suggestions - подсказки адресов

### Сервис DeliveryCalculationService
- Скопировать из express проекта
- Адаптировать для работы с DeliverySetting моделью

## 3. Admin Panel

### Страница SettingsPage.vue
- Добавить вкладку "Доставка"
- Или создать отдельную страницу DeliverySettingsPage.vue
- Добавить роут `/admin/settings/delivery`

### Компоненты:
- Форма настроек (как в express)
- Зоны доставки (динамическое добавление/удаление)
- Геокодирование адреса начала доставки

## 4. Frontend (MiniApp)

### Компоненты:
1. **DeliveryModeToggle** - переключение самовывоз/доставка
   - Отображается ниже header на CatalogPage
   - Сохраняет выбор в localStorage
   - Передает в CheckoutPage через state

2. **DeliveryProgressIndicator** - прогрессбар
   - Показывает прогресс до минимального заказа
   - Показывает прогресс до бесплатной доставки
   - Отображается над BottomNavigation

### Интеграция:
- **CatalogPage**: DeliveryModeToggle ниже header, DeliveryProgressIndicator
- **CheckoutPage**: Использование выбранного режима, расчет стоимости доставки

### API методы:
- `deliverySettingsAPI.getSettings(shopId)` - получить настройки
- `deliverySettingsAPI.calculateCost(address, cartTotal)` - расчет стоимости
- `deliverySettingsAPI.getAddressSuggestions(query, city)` - подсказки адресов

## 5. Логика работы

### Привязка к user/shop:
- В админке: настройки привязаны к выбранному магазину (shopStore.selectedShopId)
- На фронтенде: настройки получаются по shop_id из URL (shopSlug)
- При создании/обновлении: автоматически привязывается к текущему user и выбранному shop

### Проверка доступа:
- Пользователь может управлять настройками только для магазинов, к которым имеет доступ
- Использовать `$user->hasAccessToShop($shopId)`

## 6. Порядок реализации

1. ✅ Спроектировать структуру
2. Создать миграцию delivery_settings
3. Создать модель DeliverySetting
4. Создать DeliverySettingsRequest
5. Создать DeliveryCalculationService
6. Создать DeliverySettingsController
7. Добавить API роуты
8. Создать DeliverySettingsPage.vue в админке
9. Добавить роут в router
10. Создать DeliveryModeToggle на фронтенде
11. Создать DeliveryProgressIndicator на фронтенде
12. Интегрировать в CatalogPage
13. Интегрировать в CheckoutPage
14. Добавить API методы на фронтенде
15. Тестирование
