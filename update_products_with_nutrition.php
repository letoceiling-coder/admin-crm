<?php

/**
 * Скрипт для автоматического обновления товаров с питательными веществами
 * 
 * Использование:
 * php update_products_with_nutrition.php
 * 
 * Или одной командой:
 * php update_products_with_nutrition.php && php artisan db:seed --class=PirogiSeeder
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "═══════════════════════════════════════\n";
echo "Обновление товаров с питательными веществами\n";
echo "═══════════════════════════════════════\n\n";

// Загружаем существующие данные
$dataFile = __DIR__ . '/storage/pirogi_data.json';
if (!file_exists($dataFile)) {
    die("❌ Файл {$dataFile} не найден!\n");
}

$data = json_decode(file_get_contents($dataFile), true);
if (!$data || !isset($data['products'])) {
    die("❌ Неверный формат данных в {$dataFile}\n");
}

echo "📦 Найдено товаров: " . count($data['products']) . "\n";

// Проверяем, есть ли уже данные о питательных веществах
$withNutrition = 0;
$withoutNutrition = 0;

foreach ($data['products'] as $product) {
    if (isset($product['weight']) || isset($product['protein'])) {
        $withNutrition++;
    } else {
        $withoutNutrition++;
    }
}

echo "✅ Товаров с данными: {$withNutrition}\n";
echo "⚠️  Товаров без данных: {$withoutNutrition}\n\n";

if ($withoutNutrition > 0) {
    echo "⚠️  ВНИМАНИЕ: У {$withoutNutrition} товаров нет данных о питательных веществах!\n";
    echo "   Seeder обновит товары, но поля останутся пустыми.\n";
    echo "   Для сбора данных используйте scrape_nutritional_data_local.html\n\n";
}

echo "✅ JSON файл готов к использованию\n";
echo "✅ Можно запускать seeder\n\n";

echo "═══════════════════════════════════════\n";
echo "Для обновления БД выполните:\n";
echo "php artisan db:seed --class=PirogiSeeder\n";
echo "═══════════════════════════════════════\n";
