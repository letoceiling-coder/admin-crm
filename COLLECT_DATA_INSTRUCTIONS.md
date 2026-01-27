# Инструкция по сбору данных о питательных веществах

## Вариант 1: Node.js скрипт (может быть медленным)

### Обычная версия:
```bash
npm run collect
```

### Упрощенная версия (быстрее, через категории):
```bash
npm run collect-simple
```

**Проблемы:**
- Может падать с таймаутами
- Работает медленно (5-10 минут)
- Может быть заблокирован сайтом

## Вариант 2: HTML скрипт в браузере (РЕКОМЕНДУЕТСЯ)

### Шаги:

1. **Откройте `scrape_nutritional_data_local.html` в браузере**

2. **Скопируйте содержимое `storage/pirogi_data.json`**:
   ```bash
   # Windows
   type storage\pirogi_data.json
   
   # Linux/Mac
   cat storage/pirogi_data.json
   ```

3. **Вставьте JSON в поле "JSON данные товаров"** в HTML файле

4. **Нажмите "Начать сбор данных"**
   - Скрипт откроет страницы товаров автоматически
   - Соберет данные
   - Обновит JSON

5. **Скопируйте обновленный JSON** из поля "Результат"

6. **Сохраните в `storage/pirogi_data.json`**

7. **Задеплойте обновленный файл на сервер**

## Вариант 3: Ручной сбор (если автоматика не работает)

1. Откройте страницу товара на pirogi.ru
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
4. Скопируйте результат и добавьте в JSON

## После сбора данных:

### Задеплойте обновленный `storage/pirogi_data.json` на сервер

### На сервере выполните:

```bash
php artisan db:seed --class=PirogiSeeder
```

**Готово!** Все создано со всеми данными.

## Важно:

- ✅ Seeder готов и работает
- ✅ Все файлы готовы к деплою
- ⚠️ Данные нужно собрать локально (HTML скрипт - самый надежный способ)
- ✅ После сбора - одна команда на сервере
