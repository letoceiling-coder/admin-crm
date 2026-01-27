# PirogiSeeder - Инструкция

## На сервере: ОДНА КОМАНДА

```bash
php artisan db:seed --class=PirogiSeeder
```

**Все создается автоматически из `storage/pirogi_data.json`**

## Что делает seeder:

1. ✅ Очищает старые данные (товары, категории, единицы, медиа)
2. ✅ Создает категории из JSON
3. ✅ Создает единицы измерения
4. ✅ Создает товары из JSON (с питательными веществами, если есть в JSON)
5. ✅ Скачивает изображения товаров
6. ✅ Создает папки категорий в медиа-библиотеке

## Требования:

- ✅ Файл `storage/pirogi_data.json` должен существовать
- ✅ Файл `storage/pirogi_products_images.json` должен существовать
- ✅ Миграции должны быть выполнены (`php artisan migrate`)

## Структура JSON:

`storage/pirogi_data.json` должен содержать:

```json
{
  "categories": [...],
  "products": [
    {
      "name": "Салат «Мимоза»",
      "price": 220,
      "weight": 250,
      "protein": 8,
      "fat": 10,
      "carbs": 35.5,
      "calories": 255
    }
  ]
}
```

## Перед деплоем:

**Локально соберите данные:**

```bash
npm install
npm run collect
```

Это обновит `storage/pirogi_data.json` с данными о питательных веществах.

**Затем задеплойте обновленный JSON на сервер.**

## На сервере:

**Только одна команда:**

```bash
php artisan db:seed --class=PirogiSeeder
```

**Готово!** Все создано со всеми данными.
