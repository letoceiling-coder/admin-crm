# Чеклист файлов для деплоя

## Обязательные файлы для деплоя

### 1. Миграции
- ✅ `database/migrations/2026_01_27_172409_add_nutritional_info_to_products_table.php`
- ✅ `database/migrations/2026_01_27_165626_add_user_id_to_categories_table.php`
- ✅ `database/migrations/2026_01_27_165634_add_user_id_to_products_table.php`
- ✅ `database/migrations/2026_01_27_165639_add_user_id_to_units_table.php`

### 2. Модели (обновленные)
- ✅ `app/Models/Product.php` - должен содержать поля weight, protein, fat, carbs, calories в $fillable и $casts
- ✅ `app/Models/Category.php` - должен содержать user_id в $fillable
- ✅ `app/Models/Unit.php` - должен содержать user_id в $fillable

### 3. Seeder (обновленный)
- ✅ `database/seeders/PirogiSeeder.php` - должен обновлять существующие товары

### 4. Контроллеры (обновленные)
- ✅ `app/Http/Controllers/Api/ProductController.php` - должен валидировать новые поля

### 5. Frontend (обновленные)
- ✅ `resources/js/pages/admin/ProductFormPage.vue` - должен содержать поля для питательных веществ
- ✅ `resources/js/router/index.js` - должен содержать роуты для товаров

### 6. Данные (опционально)
- ⚠️ `storage/pirogi_data.json` - должен содержать weight, protein, fat, carbs, calories для товаров (если собраны)

## Проверка после деплоя

### 1. Проверить миграции
```bash
php artisan migrate:status
```
Должна быть выполнена: `2026_01_27_172409_add_nutritional_info_to_products_table`

### 2. Проверить структуру таблицы
```bash
php artisan tinker
```
```php
Schema::hasColumn('products', 'weight'); // должно вернуть true
Schema::hasColumn('products', 'protein'); // должно вернуть true
Schema::hasColumn('products', 'fat'); // должно вернуть true
Schema::hasColumn('products', 'carbs'); // должно вернуть true
Schema::hasColumn('products', 'calories'); // должно вернуть true
exit
```

### 3. Проверить seeder
```bash
php artisan db:seed --class=PirogiSeeder
```
Должен обновить товары без ошибок

### 4. Проверить в админ-панели
- Откройте `/admin/products`
- Откройте любой товар
- Должен быть раздел "Вес и питательные вещества" с полями

## Если товары не обновляются

### Проверка 1: Миграция выполнена?
```bash
php artisan migrate:status | grep add_nutritional_info
```
Если нет - выполните:
```bash
php artisan migrate
```

### Проверка 2: Поля есть в таблице?
```bash
php artisan tinker
```
```php
$product = App\Models\Product::first();
var_dump($product->getAttributes()); // проверьте наличие weight, protein, fat, carbs, calories
exit
```

### Проверка 3: Seeder обновляет?
Добавьте временный лог в seeder:
```php
$this->command->info("Обновление товара {$product->name}: " . json_encode($updateData));
```

### Проверка 4: Модель разрешает массовое присвоение?
```php
// app/Models/Product.php
protected $fillable = [
    // ... другие поля
    'weight',
    'protein',
    'fat',
    'carbs',
    'calories',
];
```

## Команда для полной проверки

```bash
# 1. Проверить миграции
php artisan migrate:status

# 2. Выполнить миграции (если нужно)
php artisan migrate --force

# 3. Проверить структуру
php artisan tinker
# Выполните проверки выше

# 4. Запустить seeder
php artisan db:seed --class=PirogiSeeder

# 5. Проверить результат
php artisan tinker
# App\Models\Product::whereNotNull('weight')->count()
```
