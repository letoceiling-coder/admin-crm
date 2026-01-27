<?php

/**
 * Финальная проверка данных перед деплоем
 */

echo "═══════════════════════════════════════\n";
echo "Проверка данных перед деплоем\n";
echo "═══════════════════════════════════════\n\n";

$dataFile = __DIR__ . '/storage/pirogi_data.json';

if (!file_exists($dataFile)) {
    die("❌ Файл {$dataFile} не найден!\n");
}

$data = json_decode(file_get_contents($dataFile), true);

if (!$data || !isset($data['products'])) {
    die("❌ Неверный формат данных в {$dataFile}\n");
}

echo "📦 Категории: " . count($data['categories']) . "\n";
echo "📦 Товары: " . count($data['products']) . "\n\n";

// Статистика по питательным веществам
$withWeight = 0;
$withProtein = 0;
$withFat = 0;
$withCarbs = 0;
$withCalories = 0;
$withAll = 0;
$withSome = 0;

foreach ($data['products'] as $product) {
    $hasWeight = isset($product['weight']) && $product['weight'] > 0;
    $hasProtein = isset($product['protein']) && $product['protein'] >= 0;
    $hasFat = isset($product['fat']) && $product['fat'] >= 0;
    $hasCarbs = isset($product['carbs']) && $product['carbs'] >= 0;
    $hasCalories = isset($product['calories']) && $product['calories'] > 0;
    
    if ($hasWeight) $withWeight++;
    if ($hasProtein) $withProtein++;
    if ($hasFat) $withFat++;
    if ($hasCarbs) $withCarbs++;
    if ($hasCalories) $withCalories++;
    
    if ($hasWeight && $hasProtein && $hasFat && $hasCarbs && $hasCalories) {
        $withAll++;
    }
    
    if ($hasWeight || $hasProtein || $hasFat || $hasCarbs || $hasCalories) {
        $withSome++;
    }
}

echo "📊 Статистика по питательным веществам:\n";
echo "   С весом: {$withWeight} / " . count($data['products']) . "\n";
echo "   С белками: {$withProtein} / " . count($data['products']) . "\n";
echo "   С жирами: {$withFat} / " . count($data['products']) . "\n";
echo "   С углеводами: {$withCarbs} / " . count($data['products']) . "\n";
echo "   С калориями: {$withCalories} / " . count($data['products']) . "\n";
echo "   Со всеми данными: {$withAll} / " . count($data['products']) . "\n";
echo "   С хотя бы одним параметром: {$withSome} / " . count($data['products']) . "\n\n";

// Пример товара
echo "📋 Пример товара:\n";
$sample = $data['products'][0];
echo json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

// Проверка на товары без данных
$withoutData = [];
foreach ($data['products'] as $product) {
    $hasAny = isset($product['weight']) || isset($product['protein']) || 
              isset($product['fat']) || isset($product['carbs']) || isset($product['calories']);
    if (!$hasAny) {
        $withoutData[] = $product['name'];
    }
}

if (count($withoutData) > 0) {
    echo "⚠️  Товары без данных о питательных веществах (" . count($withoutData) . "):\n";
    foreach ($withoutData as $name) {
        echo "   - {$name}\n";
    }
    echo "\n";
    echo "ℹ️  Это нормально - seeder корректно обработает такие товары (поля nullable).\n\n";
} else {
    echo "✅ Все товары имеют хотя бы некоторые данные о питательных веществах!\n\n";
}

echo "═══════════════════════════════════════\n";
echo "✅ Данные готовы к деплою!\n";
echo "═══════════════════════════════════════\n";
echo "\n";
echo "На сервере выполните:\n";
echo "php artisan db:seed --class=PirogiSeeder\n";
