# Обновление одной командой

## Для Windows (локальная разработка)

```bash
update_and_seed.bat
```

Или вручную:
```bash
php update_products_with_nutrition.php && php artisan migrate --force && php artisan db:seed --class=PirogiSeeder
```

## Для Linux/Mac

```bash
chmod +x update_and_seed.sh
./update_and_seed.sh
```

Или вручную:
```bash
php update_products_with_nutrition.php && php artisan migrate --force && php artisan db:seed --class=PirogiSeeder
```

## Что делает скрипт:

1. ✅ Проверяет наличие `storage/pirogi_data.json`
2. ✅ Проверяет наличие данных о питательных веществах
3. ✅ Выполняет миграции (добавляет новые поля)
4. ✅ Запускает seeder (обновляет товары)

## Перед запуском:

### Если данных о питательных веществах нет:

1. Откройте `scrape_nutritional_data_local.html` в браузере
2. Скопируйте содержимое `storage/pirogi_data.json` в скрипт
3. Соберите данные
4. Скопируйте обновленный JSON обратно в `storage/pirogi_data.json`
5. Запустите скрипт

### Если данные уже есть в JSON:

Просто запустите скрипт - все обновится автоматически!

## Результат:

После выполнения:
- ✅ Миграции выполнены
- ✅ Товары обновлены
- ✅ Поля питательных веществ заполнены (если были в JSON)
- ✅ Форма товара работает

## Проверка:

Откройте `/admin/products` → любой товар → раздел "Вес и питательные вещества"
