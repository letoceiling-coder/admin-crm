# ✅ Финальный чеклист для деплоя

## Перед деплоем (локально):

### 1. Собрать данные о питательных веществах:

```bash
# Windows
prepare_for_deploy.bat

# Или вручную
npm install
npm run collect
```

Это обновит `storage/pirogi_data.json` с данными о весе и питательных веществах.

### 2. Проверить файлы:

Убедитесь, что все файлы готовы:
- ✅ `database/seeders/PirogiSeeder.php` (обновлен, очищает и создает все)
- ✅ `database/migrations/2026_01_27_172409_add_nutritional_info_to_products_table.php`
- ✅ `app/Models/Product.php` (с полями weight, protein, fat, carbs, calories)
- ✅ `app/Http/Controllers/Api/ProductController.php` (с валидацией)
- ✅ `resources/js/pages/admin/ProductFormPage.vue` (с полями формы)
- ✅ `storage/pirogi_data.json` (с данными о питательных веществах)
- ✅ `storage/pirogi_products_images.json` (карта изображений)

### 3. Задеплоить на сервер:

Загрузите все файлы на сервер, включая обновленный `storage/pirogi_data.json`.

## На сервере (ОДНА КОМАНДА):

```bash
php artisan db:seed --class=PirogiSeeder
```

**ВСЕ!** Seeder автоматически:
- ✅ Очистит старые данные
- ✅ Создаст категории
- ✅ Создаст единицы измерения
- ✅ Создаст товары (с питательными веществами из JSON)
- ✅ Скачает изображения
- ✅ Создаст папки категорий

## Проверка после выполнения:

```bash
php artisan tinker
```

```php
echo "Товары: " . App\Models\Product::count() . "\n";
echo "С весом: " . App\Models\Product::whereNotNull('weight')->count() . "\n";
echo "С белками: " . App\Models\Product::whereNotNull('protein')->count() . "\n";
echo "С жирами: " . App\Models\Product::whereNotNull('fat')->count() . "\n";
echo "С углеводами: " . App\Models\Product::whereNotNull('carbs')->count() . "\n";
echo "С калориями: " . App\Models\Product::whereNotNull('calories')->count() . "\n";
exit
```

Если все правильно - все товары будут с данными о питательных веществах!
