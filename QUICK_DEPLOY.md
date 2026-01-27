# Быстрая инструкция по деплою

## После deploy выполните:

```bash
# 1. Миграции
php artisan migrate

# 2. Обновить товары (если есть обновленный pirogi_data.json)
php artisan db:seed --class=PirogiSeeder
```

## Что изменилось:

✅ Добавлены новые поля в таблицу `products`:
- `weight` - вес порции
- `protein` - белки
- `fat` - жиры
- `carbs` - углеводы
- `calories` - калорийность

✅ Seeder обновляет существующие товары новыми полями

✅ Форма товара теперь содержит поля для питательных веществ

## Проверка:

Откройте `/admin/products` → любой товар → раздел "Вес и питательные вещества"

## Если нужно собрать данные:

1. Используйте `scrape_nutritional_data_local.html` локально
2. Обновите `storage/pirogi_data.json` на сервере
3. Запустите seeder снова

Подробности: `DEPLOY_INSTRUCTIONS.md`
