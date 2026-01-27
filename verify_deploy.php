<?php

/**
 * Скрипт для проверки готовности к деплою
 * Использование: php verify_deploy.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════\n";
echo "Проверка готовности к деплою\n";
echo "═══════════════════════════════════════\n\n";

$errors = [];
$warnings = [];

// 1. Проверка миграции
echo "1. Проверка миграции...\n";
$migrationFile = 'database/migrations/2026_01_27_172409_add_nutritional_info_to_products_table.php';
if (!file_exists($migrationFile)) {
    $errors[] = "Миграция не найдена: {$migrationFile}";
    echo "   ❌ Миграция не найдена\n";
} else {
    echo "   ✅ Миграция найдена\n";
}

// 2. Проверка полей в таблице
echo "\n2. Проверка полей в таблице products...\n";
$requiredFields = ['weight', 'protein', 'fat', 'carbs', 'calories'];
foreach ($requiredFields as $field) {
    if (Schema::hasColumn('products', $field)) {
        echo "   ✅ Поле '{$field}' существует\n";
    } else {
        $errors[] = "Поле '{$field}' отсутствует в таблице products";
        echo "   ❌ Поле '{$field}' отсутствует\n";
    }
}

// 3. Проверка модели Product
echo "\n3. Проверка модели Product...\n";
$modelFile = 'app/Models/Product.php';
if (!file_exists($modelFile)) {
    $errors[] = "Модель не найдена: {$modelFile}";
    echo "   ❌ Модель не найдена\n";
} else {
    $content = file_get_contents($modelFile);
    $allFieldsPresent = true;
    foreach ($requiredFields as $field) {
        if (strpos($content, "'{$field}'") === false && strpos($content, "\"{$field}\"") === false) {
            $errors[] = "Поле '{$field}' отсутствует в \$fillable модели Product";
            echo "   ❌ Поле '{$field}' отсутствует в \$fillable\n";
            $allFieldsPresent = false;
        }
    }
    if ($allFieldsPresent) {
        echo "   ✅ Все поля присутствуют в \$fillable\n";
    }
}

// 4. Проверка seeder
echo "\n4. Проверка seeder...\n";
$seederFile = 'database/seeders/PirogiSeeder.php';
if (!file_exists($seederFile)) {
    $errors[] = "Seeder не найден: {$seederFile}";
    echo "   ❌ Seeder не найден\n";
} else {
    $content = file_get_contents($seederFile);
    if (strpos($content, 'extractNutritionalData') !== false) {
        echo "   ✅ Метод extractNutritionalData найден\n";
    } else {
        $warnings[] = "Метод extractNutritionalData не найден в seeder";
        echo "   ⚠️  Метод extractNutritionalData не найден\n";
    }
    
    if (strpos($content, 'wasRecentlyCreated') !== false) {
        echo "   ✅ Логика обновления найдена\n";
    } else {
        $warnings[] = "Логика обновления не найдена в seeder";
        echo "   ⚠️  Логика обновления не найдена\n";
    }
}

// 5. Проверка данных
echo "\n5. Проверка данных...\n";
$dataFile = 'storage/pirogi_data.json';
if (!file_exists($dataFile)) {
    $warnings[] = "Файл данных не найден: {$dataFile}";
    echo "   ⚠️  Файл данных не найден\n";
} else {
    $data = json_decode(file_get_contents($dataFile), true);
    if ($data && isset($data['products'])) {
        $withNutrition = 0;
        foreach ($data['products'] as $product) {
            if (isset($product['weight']) || isset($product['protein'])) {
                $withNutrition++;
            }
        }
        echo "   ✅ Найдено товаров: " . count($data['products']) . "\n";
        echo "   " . ($withNutrition > 0 ? "✅" : "⚠️ ") . " Товаров с данными о питательных веществах: {$withNutrition}\n";
        if ($withNutrition === 0) {
            $warnings[] = "В JSON нет данных о питательных веществах";
        }
    } else {
        $warnings[] = "Неверный формат данных в {$dataFile}";
        echo "   ⚠️  Неверный формат данных\n";
    }
}

// 6. Проверка существующих товаров
echo "\n6. Проверка существующих товаров...\n";
try {
    $productsCount = DB::table('products')->count();
    $productsWithWeight = DB::table('products')->whereNotNull('weight')->count();
    echo "   ✅ Всего товаров: {$productsCount}\n";
    echo "   " . ($productsWithWeight > 0 ? "✅" : "⚠️ ") . " Товаров с весом: {$productsWithWeight}\n";
} catch (\Exception $e) {
    $warnings[] = "Не удалось проверить товары: " . $e->getMessage();
    echo "   ⚠️  Ошибка проверки: " . $e->getMessage() . "\n";
}

// Итоги
echo "\n═══════════════════════════════════════\n";
if (empty($errors)) {
    echo "✅ Все проверки пройдены!\n";
    if (!empty($warnings)) {
        echo "\n⚠️  Предупреждения:\n";
        foreach ($warnings as $warning) {
            echo "   - {$warning}\n";
        }
    }
    echo "\nМожно выполнять деплой!\n";
} else {
    echo "❌ Найдены ошибки:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
    if (!empty($warnings)) {
        echo "\n⚠️  Предупреждения:\n";
        foreach ($warnings as $warning) {
            echo "   - {$warning}\n";
        }
    }
    echo "\nИсправьте ошибки перед деплоем!\n";
    exit(1);
}
echo "═══════════════════════════════════════\n";
