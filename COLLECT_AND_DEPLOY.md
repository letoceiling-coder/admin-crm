# Инструкция: Собрать данные локально и деплой одной командой

## Шаг 1: Локально - собрать данные о питательных веществах

### Установка зависимостей (один раз):

```bash
npm install
```

### Сбор данных (одна команда):

```bash
npm run collect
```

Или:

```bash
node collect_nutritional_data.js
```

**Что делает:**
- ✅ Открывает браузер
- ✅ Переходит на pirogi.ru
- ✅ Ищет каждый товар
- ✅ Собирает данные о весе и питательных веществах
- ✅ Обновляет `storage/pirogi_data.json`

**Время выполнения:** ~5-10 минут (73 товара)

## Шаг 2: Деплой на сервер

### Загрузите обновленный файл:

```bash
# Загрузите storage/pirogi_data.json на сервер
```

### На сервере выполните:

```bash
php artisan db:seed --class=PirogiSeeder
```

**Одна команда - все готово!**

## Альтернатива: Если Node.js не установлен

### Вариант 1: Использовать HTML скрипт

1. Откройте `scrape_nutritional_data_local.html` в браузере
2. Скопируйте содержимое `storage/pirogi_data.json`
3. Соберите данные
4. Скопируйте обновленный JSON обратно в файл

### Вариант 2: Ручной сбор через консоль браузера

Откройте страницу товара на pirogi.ru и выполните в консоли (F12):

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

Скопируйте результат и добавьте в JSON.

## Результат

После сбора данных и запуска seeder:
- ✅ Все товары созданы
- ✅ Все изображения скачаны
- ✅ Все питательные вещества заполнены
- ✅ Готово к использованию!
