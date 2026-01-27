# Инструкция по сбору питательных веществ для товаров

## Проблема

Автоматический скрипт `scrape_nutritional_data.php` не может загрузить страницы с сервера из-за ограничений доступа к pirogi.ru.

## Решение: Сбор данных локально

### Вариант 1: HTML скрипт (самый простой)

1. **Скачайте файл `scrape_nutritional_data_local.html`** на локальный компьютер

2. **Откройте его в браузере** (просто двойной клик по файлу)

3. **Скопируйте данные с сервера:**
   ```bash
   # На сервере
   cat storage/pirogi_data.json
   ```

4. **Вставьте JSON** в поле "JSON данные товаров" в HTML файле

5. **Нажмите "Начать сбор данных"**
   - Скрипт будет открывать страницы товаров на pirogi.ru
   - Собирать данные о весе и питательных веществах
   - Обновлять JSON автоматически

6. **Скопируйте результат** из поля "Результат (обновленный JSON)"

7. **Загрузите на сервер:**
   ```bash
   # Сохраните обновленный JSON в storage/pirogi_data.json на сервере
   nano storage/pirogi_data.json
   # Вставьте обновленный JSON
   ```

8. **Запустите seeder:**
   ```bash
   php artisan db:seed --class=PirogiSeeder
   ```

### Вариант 2: Ручной сбор через консоль браузера

Если HTML скрипт не работает:

1. Откройте страницу товара на pirogi.ru (например, через поиск)

2. Откройте консоль браузера (F12 → Console)

3. Выполните:
   ```javascript
   function extractData() {
       const data = {};
       const weightEl = document.querySelector('.id-weight');
       if (weightEl) {
           const match = weightEl.textContent.match(/(\d+)\s*гр?/i);
           if (match) data.weight = parseInt(match[1]);
       }
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
   extractData();
   ```

4. Скопируйте результат и добавьте в `storage/pirogi_data.json`

### Вариант 3: Прямое редактирование JSON

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

Добавьте к каждому товару в JSON следующие поля:

- `weight` (integer) - вес порции в граммах, например: `250`
- `protein` (float) - белки в граммах, например: `8.0`
- `fat` (float) - жиры в граммах, например: `10.0`
- `carbs` (float) - углеводы в граммах, например: `35.5`
- `calories` (integer) - калорийность в ккал, например: `255`

## После обновления JSON

После того как обновите `storage/pirogi_data.json` на сервере, запустите:

```bash
php artisan db:seed --class=PirogiSeeder
```

Seeder обновит все существующие товары новыми данными о питательных веществах и весе.

## Проверка

После выполнения seeder проверьте в админ-панели:
- `/admin/products` - откройте любой товар
- В разделе "Вес и питательные вещества" должны быть заполнены поля
