# Финальная инструкция по деплою

## Проблема: товары не обновляются

### Причина
Seeder обновляет товары только если в JSON есть данные о питательных веществах. Если данных нет, поля остаются пустыми (это нормально).

## Что проверить на сервере

### 1. Проверка файлов (после деплоя)

```bash
# Проверьте наличие всех файлов
php verify_deploy.php
```

Должно показать: ✅ Все проверки пройдены!

### 2. Проверка миграции

```bash
php artisan migrate:status | grep add_nutritional_info
```

Если миграция не выполнена:
```bash
php artisan migrate --force
```

### 3. Проверка структуры таблицы

```bash
php artisan tinker
```
```php
Schema::hasColumn('products', 'weight'); // должно вернуть true
Schema::hasColumn('products', 'protein'); // должно вернуть true
exit
```

### 4. Проверка seeder

```bash
php artisan db:seed --class=PirogiSeeder
```

**Ожидаемый результат:**
- Товары обновлены ✓
- Если в JSON есть данные о питательных веществах - они обновятся
- Если данных нет - поля останутся пустыми (это нормально)

### 5. Проверка результата

```bash
php artisan tinker
```
```php
// Проверьте, что поля существуют
$p = App\Models\Product::first();
var_dump($p->weight); // может быть null, если данных нет в JSON
var_dump($p->protein); // может быть null, если данных нет в JSON

// Проверьте, что поля можно установить
$p->weight = 250;
$p->save();
echo "Weight установлен: " . $p->weight . "\n";
exit
```

## Если товары все еще не обновляются

### Шаг 1: Проверьте модель Product

```bash
cat app/Models/Product.php | grep -A 20 fillable
```

Должно содержать:
```php
protected $fillable = [
    // ...
    'weight',
    'protein',
    'fat',
    'carbs',
    'calories',
];
```

### Шаг 2: Проверьте seeder

```bash
cat database/seeders/PirogiSeeder.php | grep -A 10 "wasRecentlyCreated"
```

Должно быть:
```php
if ($product->wasRecentlyCreated === false) {
    // логика обновления
}
```

### Шаг 3: Добавьте отладочный вывод

Временно добавьте в `database/seeders/PirogiSeeder.php` после строки 204:

```php
$this->command->info("DEBUG: Товар {$product->id} существовал, обновляем...");
$this->command->info("DEBUG: Данные для обновления: " . json_encode($updateData));
```

Запустите seeder снова и проверьте вывод.

## Важно!

**Товары обновляются только если:**
1. ✅ Товар уже существует (wasRecentlyCreated === false)
2. ✅ В JSON есть данные о питательных веществах (weight, protein, и т.д.)
3. ✅ Данные не null

**Если в JSON нет данных о питательных веществах:**
- Поля останутся пустыми (null)
- Это нормально!
- Данные можно добавить позже через админ-панель или обновив JSON

## Команда для полной проверки

```bash
# 1. Проверка файлов
php verify_deploy.php

# 2. Миграции (если нужно)
php artisan migrate --force

# 3. Seeder
php artisan db:seed --class=PirogiSeeder

# 4. Проверка
php artisan tinker
# App\Models\Product::first()->weight
```

## Файлы для деплоя (чеклист)

✅ `database/migrations/2026_01_27_172409_add_nutritional_info_to_products_table.php`
✅ `app/Models/Product.php` (с weight, protein, fat, carbs, calories в $fillable)
✅ `database/seeders/PirogiSeeder.php` (с логикой обновления)
✅ `app/Http/Controllers/Api/ProductController.php` (с валидацией новых полей)
✅ `resources/js/pages/admin/ProductFormPage.vue` (с полями формы)

Подробности: `FILES_FOR_DEPLOY.md`
