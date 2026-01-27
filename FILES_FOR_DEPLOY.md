# Файлы, которые должны попасть на сервер при деплое

## ✅ Обязательные файлы

### Миграции
```
database/migrations/2026_01_27_172409_add_nutritional_info_to_products_table.php
database/migrations/2026_01_27_165626_add_user_id_to_categories_table.php
database/migrations/2026_01_27_165634_add_user_id_to_products_table.php
database/migrations/2026_01_27_165639_add_user_id_to_units_table.php
```

### Модели (обновленные)
```
app/Models/Product.php          ← ДОЛЖЕН содержать weight, protein, fat, carbs, calories в $fillable
app/Models/Category.php         ← ДОЛЖЕН содержать user_id в $fillable
app/Models/Unit.php              ← ДОЛЖЕН содержать user_id в $fillable
```

### Seeder (обновленный)
```
database/seeders/PirogiSeeder.php  ← ДОЛЖЕН обновлять существующие товары
```

### Контроллеры (обновленные)
```
app/Http/Controllers/Api/ProductController.php  ← ДОЛЖЕН валидировать новые поля
```

### Frontend (обновленные)
```
resources/js/pages/admin/ProductFormPage.vue    ← ДОЛЖЕН содержать поля для питательных веществ
resources/js/router/index.js                    ← Должен содержать роуты
```

## ⚠️ Опциональные файлы

### Данные
```
storage/pirogi_data.json  ← Может содержать weight, protein, fat, carbs, calories
```

### Скрипты (для локальной разработки)
```
scrape_nutritional_data_local.html
update_products_with_nutrition.php
verify_deploy.php
update_all.bat
```

## 🔍 Проверка после деплоя

### 1. Запустите проверку
```bash
php verify_deploy.php
```

### 2. Проверьте миграции
```bash
php artisan migrate:status | grep add_nutritional_info
```

### 3. Проверьте структуру таблицы
```bash
php artisan tinker
```
```php
Schema::hasColumn('products', 'weight'); // true
exit
```

### 4. Запустите seeder
```bash
php artisan db:seed --class=PirogiSeeder
```

### 5. Проверьте результат
```bash
php artisan tinker
```
```php
$p = App\Models\Product::first();
echo "Weight: " . ($p->weight ?? 'null') . "\n";
echo "Protein: " . ($p->protein ?? 'null') . "\n";
exit
```

## ❌ Если товары не обновляются

### Проверка 1: Файлы на месте?
```bash
ls -la app/Models/Product.php
ls -la database/seeders/PirogiSeeder.php
ls -la database/migrations/*add_nutritional_info*
```

### Проверка 2: Миграция выполнена?
```bash
php artisan migrate:status
```

Если миграция не выполнена:
```bash
php artisan migrate --force
```

### Проверка 3: Модель разрешает массовое присвоение?
Проверьте `app/Models/Product.php`:
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

### Проверка 4: Seeder обновляет?
Добавьте временный лог в `database/seeders/PirogiSeeder.php` после строки 213:
```php
$this->command->info("DEBUG: Обновление товара {$product->id}: " . json_encode($updateData));
```

## 🚀 Команда для полного обновления

```bash
# 1. Проверка
php verify_deploy.php

# 2. Миграции
php artisan migrate --force

# 3. Seeder
php artisan db:seed --class=PirogiSeeder

# 4. Проверка результата
php artisan tinker
# App\Models\Product::whereNotNull('weight')->count()
```
