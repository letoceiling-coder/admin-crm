# Ручной сбор питательных веществ с pirogi.ru

## Проблема

Автоматический скрипт не может загрузить страницы с сервера из-за ограничений доступа. Решение - собрать данные локально через браузер.

## Способ 1: Использование HTML скрипта (рекомендуется)

### Шаг 1: Откройте HTML файл

Откройте файл `scrape_nutritional_data_local.html` в браузере (локально, не на сервере).

### Шаг 2: Скопируйте данные

1. На сервере скопируйте содержимое `storage/pirogi_data.json`
2. Вставьте в поле "JSON данные товаров" в HTML файле

### Шаг 3: Запустите сбор

1. Нажмите "Начать сбор данных"
2. Скрипт будет открывать страницы товаров на pirogi.ru
3. Собирать данные о весе и питательных веществах
4. После завершения скопируйте обновленный JSON из поля "Результат"

### Шаг 4: Сохраните на сервере

1. Загрузите обновленный JSON на сервер
2. Сохраните в `storage/pirogi_data.json`
3. Запустите seeder: `php artisan db:seed --class=PirogiSeeder`

## Способ 2: Ручной сбор через консоль браузера

Если HTML скрипт не работает, можно собрать данные вручную через консоль браузера:

### Шаг 1: Откройте страницу товара

Откройте страницу товара на pirogi.ru, например: `https://pirogi.ru/shop/92` (где 92 - ID товара)

### Шаг 2: Выполните в консоли браузера (F12)

```javascript
// Функция для извлечения данных со страницы
function extractNutritionalData() {
    const data = {};
    
    // Вес
    const weightEl = document.querySelector('.id-weight');
    if (weightEl) {
        const match = weightEl.textContent.match(/(\d+)\s*гр?/i);
        if (match) data.weight = parseInt(match[1]);
    }
    
    // Питательные вещества
    document.querySelectorAll('.id-params-item').forEach(item => {
        const title = item.querySelector('.title')?.textContent.trim().toLowerCase();
        const value = item.querySelector('.value')?.textContent.trim();
        const match = value?.match(/([\d.]+)/);
        
        if (match) {
            const num = parseFloat(match[1]);
            if (title?.includes('белк')) data.protein = num;
            else if (title?.includes('жир')) data.fat = num;
            else if (title?.includes('углевод')) data.carbs = num;
            else if (title?.includes('калори')) data.calories = parseInt(num);
        }
    });
    
    return data;
}

// Выполните функцию
extractNutritionalData();
```

### Шаг 3: Скопируйте результат

Скопируйте результат и добавьте в `storage/pirogi_data.json` для соответствующего товара.

## Способ 3: Прямое редактирование JSON

Можно вручную добавить данные в `storage/pirogi_data.json`:

```json
{
  "name": "Салат «Мимоза»",
  "categoryId": "2",
  "categoryName": "Салаты",
  "price": 220,
  "weight": 250,
  "protein": 8,
  "fat": 10,
  "carbs": 35.5,
  "calories": 255
}
```

## Структура данных

Добавьте следующие поля к каждому товару в JSON:

- `weight` (integer) - вес порции в граммах
- `protein` (float) - белки в граммах
- `fat` (float) - жиры в граммах
- `carbs` (float) - углеводы в граммах
- `calories` (integer) - калорийность в ккал

## После обновления JSON

После того как обновите `storage/pirogi_data.json` на сервере, запустите:

```bash
php artisan db:seed --class=PirogiSeeder
```

Seeder обновит все товары новыми данными.
