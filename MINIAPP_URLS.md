# Ссылки для доступа к Telegram Mini App

## Текущие магазины в БД:

| ID | Название | Slug | URL |
|---|---|---|---|
| 1 | Пироги | pirogi | https://crm.neeklo.ru/pirogi |

## Формат URL:

```
https://crm.neeklo.ru/{shopSlug}
```

Где `{shopSlug}` - это поле `slug` из таблицы `shops` в БД.

## Примеры ссылок:

### Для магазина "Пироги" (slug: pirogi):

1. **Каталог товаров:**
   ```
   https://crm.neeklo.ru/pirogi
   ```

2. **Детальная страница товара:**
   ```
   https://crm.neeklo.ru/pirogi/product/{productId}
   ```

3. **Корзина:**
   ```
   https://crm.neeklo.ru/pirogi/cart
   ```

4. **Оформление заказа:**
   ```
   https://crm.neeklo.ru/pirogi/checkout
   ```

## Настройка в Telegram Bot:

В @BotFather при создании Web App укажите:
```
Web App URL: https://crm.neeklo.ru/pirogi
```

## Проверка магазинов в БД:

```bash
php artisan tinker
>>> \App\Models\Shop::select('id', 'name', 'slug')->get();
```

## Добавление нового магазина:

1. Создайте магазин через админ-панель: `/admin/shops`
2. Slug автоматически сгенерируется из названия
3. Используйте slug в URL: `https://crm.neeklo.ru/{slug}`

## Важно:

- Файлы miniapp должны быть собраны и находиться в `public/miniapp/`
- Для сборки выполните: `php artisan deploy`
- Если файлы отсутствуют, будет показана ошибка 404
