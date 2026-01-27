# План реализации настроек способов оплаты

## Анализ проекта express

### Структура в express:
1. **Модель PaymentMethod** - способы оплаты (cash, card, bank_transfer, online, other)
2. **Модель PaymentSetting** - настройки платежных систем (ЮКасса и т.д.)
3. **PaymentMethodController** - управление способами оплаты (CRUD)
4. **PaymentMethods.vue** - страница со списком способов оплаты
5. **PaymentMethodEdit.vue** - страница редактирования способа оплаты

### Что нужно изменить для текущего проекта:

## 1. База данных

### Миграция: `create_payment_method_settings_table`
```php
- id
- user_id (nullable) - пользователь, создавший настройки
- shop_id (nullable) - магазин, к которому привязаны настройки
- payment_method_code (string) - код способа оплаты ('cash', 'yookassa')
- is_enabled (boolean) - включен ли способ оплаты
- is_default (boolean) - способ оплаты по умолчанию
- available_for_delivery (boolean) - доступен при доставке
- available_for_pickup (boolean) - доступен при самовывозе
- sort_order (integer) - порядок сортировки
- discount_type (enum: 'none', 'percentage', 'fixed') - тип скидки
- discount_value (decimal 10,2 nullable) - значение скидки
- min_cart_amount (decimal 10,2 nullable) - минимальная сумма корзины
- show_notification (boolean) - показывать уведомление
- notification_text (text nullable) - текст уведомления
- settings (json nullable) - дополнительные настройки (для ЮКассы)
- timestamps
- unique(user_id, shop_id, payment_method_code) - одна запись на комбинацию
```

## 2. Backend

### Модель PaymentMethodSetting
- Связи: `belongsTo(User)`, `belongsTo(Shop)`
- Метод `getSettings($userId, $shopId)` - получить настройки для user/shop
- Метод `calculateDiscount($cartAmount)` - расчет скидки
- Метод `getNotificationMessage($cartAmount)` - получить текст уведомления

### Контроллер PaymentMethodSettingsController
- `index()` - GET /admin/payment-methods - получить настройки для текущего user/shop
- `update()` - PUT /admin/payment-methods/{code} - обновить настройки способа оплаты
- `getSettings()` - GET /api/v1/payment-methods?shop_id=X - публичный для фронтенда

### Предопределенные способы оплаты:
- `cash` - Наличные
- `yookassa` - ЮКасса

## 3. Admin Panel

### Страница PaymentMethodSettingsPage.vue
- Список способов оплаты (cash, yookassa)
- Для каждого способа: форма настроек
- Без кнопок создания/удаления
- Только редактирование настроек

### Роут `/admin/payment-methods`

## 4. Frontend (MiniApp)

### Интеграция в CheckoutPage:
- Выбор способа оплаты
- Отображение скидки (если есть)
- Отображение уведомления (если настроено)
- Расчет итоговой суммы с учетом скидки

### API методы:
- `paymentMethodsApi.getSettings(shopId)` - получить способы оплаты
- `paymentMethodsApi.calculateDiscount(methodCode, cartAmount, shopId)` - рассчитать скидку

## 5. Логика работы

### Привязка к user/shop:
- В админке: настройки привязаны к выбранному магазину (shopStore.selectedShopId)
- На фронтенде: настройки получаются по shop_id из URL (shopSlug)
- При создании/обновлении: автоматически привязывается к текущему user и выбранному shop

### Проверка доступа:
- Пользователь может управлять настройками только для магазинов, к которым имеет доступ
- Использовать `$user->hasAccessToShop($shopId)`
