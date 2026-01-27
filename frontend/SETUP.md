# Инструкция по настройке Telegram Mini App

## ✅ Что уже выполнено:

1. ✅ Создан React проект с Vite и TypeScript
2. ✅ Настроена структура проекта (компоненты, страницы, store)
3. ✅ Интегрирован Telegram Web App SDK
4. ✅ Созданы API сервисы для работы с бекендом
5. ✅ Реализован роутинг по названию магазина (`/:shopSlug`)
6. ✅ Созданы страницы:
   - Каталог товаров
   - Детальная страница товара
   - Корзина
   - Оформление заказа
7. ✅ Настроен дизайн в стиле pirogi.ru
8. ✅ Добавлены публичные API endpoints на бекенде

## 📋 Что нужно сделать:

### 1. Настройка окружения

Создайте файл `.env` в папке `frontend/`:
```env
VITE_API_BASE_URL=https://crm.neeklo.ru/api
```

### 2. Настройка бекенда

Убедитесь, что в Laravel:
- ✅ Добавлены публичные роуты для Telegram Mini App
- ✅ Магазины имеют поле `slug` (автоматически генерируется из названия)
- ✅ API endpoints доступны без авторизации для публичных данных

### 3. Настройка Telegram бота

1. Создайте бота через @BotFather
2. Получите токен бота
3. В админ-панели CRM добавьте токен в настройки магазина
4. Настройте Web App для бота:
   ```
   /newapp
   Выберите бота
   Название: Каталог магазина
   Описание: Каталог товаров
   Фото: (опционально)
   Web App URL: https://crm.neeklo.ru/название-магазина
   ```

### 4. Деплой frontend

#### Вариант 1: Статический хостинг
```bash
cd frontend
npm run build
# Загрузите содержимое папки dist/ на сервер
```

#### Вариант 2: Интеграция с Laravel
Скопируйте собранные файлы в `public/miniapp/`:
```bash
cd frontend
npm run build
cp -r dist/* ../public/miniapp/
```

### 5. Настройка роутинга в Laravel

Добавьте в `routes/web.php`:
```php
// Роут для Telegram Mini App
Route::get('/{shopSlug}', function ($shopSlug) {
    // Проверяем существование магазина
    $shop = \App\Models\Shop::where('slug', $shopSlug)
        ->orWhere('name', $shopSlug)
        ->first();
    
    if (!$shop) {
        abort(404);
    }
    
    // Отдаем index.html для SPA
    return response()->file(public_path('miniapp/index.html'));
})->where('shopSlug', '[a-zA-Z0-9\-_]+');

// Роуты для статических файлов Mini App
Route::get('/miniapp/{any}', function ($any) {
    $path = public_path("miniapp/{$any}");
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404);
})->where('any', '.*');
```

## 🔧 Дополнительные настройки

### Интеграция заказов

В файле `src/pages/CheckoutPage.tsx` нужно реализовать отправку заказа на бекенд:

```typescript
// Добавьте в src/services/api.ts
export const orderApi = {
  create: async (orderData: {
    shop_id: number;
    items: Array<{ product_id: number; quantity: number }>;
    customer_name: string;
    customer_phone: string;
    delivery_address: string;
    comment?: string;
    total_amount: number;
  }): Promise<any> => {
    return fetchApi('/orders', {
      method: 'POST',
      body: JSON.stringify(orderData),
    });
  },
};
```

### Получение данных пользователя из Telegram

Данные пользователя автоматически доступны через:
```typescript
window.Telegram?.WebApp?.initDataUnsafe?.user
```

## 📱 Тестирование

1. Запустите dev сервер: `npm run dev`
2. Откройте в браузере: `http://localhost:3000/название-магазина`
3. Для тестирования в Telegram используйте @BotFather для создания тестового бота

## 🐛 Возможные проблемы

1. **CORS ошибки**: Убедитесь, что бекенд разрешает запросы с вашего домена
2. **404 на роутах**: Настройте правильный роутинг в Laravel
3. **Изображения не загружаются**: Проверьте пути к изображениям в API

## 📚 Документация

- [Telegram Web App](https://core.telegram.org/bots/webapps)
- [React Router](https://reactrouter.com/)
- [Zustand](https://zustand-demo.pmnd.rs/)
- [TanStack Query](https://tanstack.com/query)
