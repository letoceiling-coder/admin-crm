<?php

/**
 * Извлечение данных о питательных веществах из data.php
 * Все данные уже есть в HTML разметке файла
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════\n";
echo "Извлечение данных из data.php\n";
echo "═══════════════════════════════════════\n\n";

$dataFile = __DIR__ . '/storage/pirogi_data.json';
$htmlFile = __DIR__ . '/data.php';

if (!file_exists($htmlFile)) {
    die("❌ Файл {$htmlFile} не найден!\n");
}

if (!file_exists($dataFile)) {
    die("❌ Файл {$dataFile} не найден!\n");
}

// Загружаем JSON
$data = json_decode(file_get_contents($dataFile), true);
if (!$data || !isset($data['products'])) {
    die("❌ Неверный формат данных в {$dataFile}\n");
}

// Загружаем HTML
$html = file_get_contents($htmlFile);

echo "📦 Найдено товаров: " . count($data['products']) . "\n";
echo "📄 Размер HTML файла: " . number_format(strlen($html)) . " байт\n\n";

$updated = 0;
$notFound = 0;

// Проходим по каждому товару
foreach ($data['products'] as $index => &$product) {
    $productName = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
    
    // Ищем блок товара по названию
    $escapedName = preg_quote($product['name'], '/');
    
    // Паттерн для поиска блока товара - ищем до закрывающего тега id-params
    // Структура: <div class="id-title">Название</div>...<div class="id-params-list...">...параметры...</div></div></div>...<div class="id-weight">вес</div>
    // Используем более широкий захват, чтобы не обрезать последний параметр
    $pattern = '/<div class="id-title">' . $escapedName . '<\/div>.*?<div class="id-params-list[^"]*">(.*?)<\/div><\/div><\/div>.*?<div class="id-weight">(.*?)<\/div>/s';
    
    if (!preg_match($pattern, $html, $blockMatch)) {
        echo "[{$index}] ⚠ {$product['name']} - блок не найден\n";
        $notFound++;
        continue;
    }
    
    $paramsHtml = $blockMatch[1] ?? '';
    $weightHtml = $blockMatch[2] ?? '';
    
    // Если параметры обрезаны (нет закрывающего тега), добавляем его для корректного парсинга
    if (substr($paramsHtml, -1) !== '>' && !str_ends_with($paramsHtml, '</div>')) {
        // Ищем последний параметр отдельно
        $lastParamPattern = '/<div class="id-params-item">.*?<div class="value">(\d+)\s*<small>ккал<\/small><\/div><div class="title">Калорийность/';
        if (preg_match($lastParamPattern, $html, $lastMatch, PREG_OFFSET_CAPTURE, strpos($html, $paramsHtml))) {
            // Находим полный блок последнего параметра
            $fullParamsPattern = '/<div class="id-params-list[^"]*">(.*?<div class="id-params-item">.*?<div class="value">\d+\s*<small>ккал<\/small><\/div><div class="title">Калорийность.*?<\/div>)/s';
            if (preg_match($fullParamsPattern, substr($html, strpos($html, '<div class="id-title">' . $escapedName)), $fullMatch)) {
                $paramsHtml = $fullMatch[1];
            }
        }
    }
    
    // Извлекаем вес
    $weight = null;
    if (preg_match('/(\d+)\s*(?:гр|г|мл|кг)/i', $weightHtml, $weightMatch)) {
        $weight = (int)$weightMatch[1];
    }
    
    // Извлекаем питательные вещества
    $protein = null;
    $fat = null;
    $carbs = null;
    $calories = null;
    
    // Извлекаем все параметры напрямую из исходного HTML
    // Ищем блок товара по названию и извлекаем все параметры из него
    $productBlockPattern = '/<div class="id-title">' . $escapedName . '<\/div>.*?<div class="id-params-list[^"]*">(.*?)<\/div><\/div><\/div>/s';
    
    if (preg_match($productBlockPattern, $html, $fullBlockMatch)) {
        $fullParamsHtml = $fullBlockMatch[1];
        
        // Извлекаем каждый параметр отдельно с более гибким паттерном
        // Белки - ищем в любом месте блока
        if (preg_match('/<div class="value">([\d.]+)\s*<small>[^<]*<\/small><\/div><div class="title">Белки<\/div>/s', $fullParamsHtml, $protMatch)) {
            $protein = (float)trim($protMatch[1]);
        }
        
        // Жиры
        if (preg_match('/<div class="value">([\d.]+)\s*<small>[^<]*<\/small><\/div><div class="title">Жиры<\/div>/s', $fullParamsHtml, $fatMatch)) {
            $fat = (float)trim($fatMatch[1]);
        }
        
        // Углеводы
        if (preg_match('/<div class="value">([\d.]+)\s*<small>[^<]*<\/small><\/div><div class="title">Углеводы<\/div>/s', $fullParamsHtml, $carbsMatch)) {
            $carbs = (float)trim($carbsMatch[1]);
        }
        
        // Калории - может быть обрезано, поэтому ищем без закрывающего тега
        if (preg_match('/<div class="value">(\d+)\s*<small>ккал<\/small><\/div><div class="title">Калорийность/s', $fullParamsHtml, $calMatch)) {
            $calories = (int)trim($calMatch[1]);
        }
    }
    
    // Если не нашли через полный блок, пробуем искать напрямую в HTML после названия товара
    if ($protein === null || $fat === null || $carbs === null) {
        $directPattern = '/<div class="id-title">' . $escapedName . '<\/div>.*?<div class="value">([\d.]+)\s*<small>[^<]*<\/small><\/div><div class="title">Белки<\/div>.*?<div class="value">([\d.]+)\s*<small>[^<]*<\/small><\/div><div class="title">Жиры<\/div>.*?<div class="value">([\d.]+)\s*<small>[^<]*<\/small><\/div><div class="title">Углеводы<\/div>/s';
        if (preg_match($directPattern, $html, $directMatch)) {
            if ($protein === null) $protein = (float)trim($directMatch[1]);
            if ($fat === null) $fat = (float)trim($directMatch[2]);
            if ($carbs === null) $carbs = (float)trim($directMatch[3]);
        }
    }
    
    // Если калории не найдены, пробуем найти их отдельно в исходном HTML
    if ($calories === null) {
        $calPattern = '/<div class="id-title">' . $escapedName . '<\/div>.*?<div class="value">(\d+)\s*<small>ккал<\/small><\/div><div class="title">Калорийность/s';
        if (preg_match($calPattern, $html, $calMatch)) {
            $calories = (int)$calMatch[1];
        }
    }
    
    // Обновляем данные товара (только если они есть)
    $hasData = false;
    if ($weight !== null && $weight > 0) {
        $product['weight'] = $weight;
        $hasData = true;
    }
    if ($protein !== null && $protein >= 0) {
        $product['protein'] = $protein;
        $hasData = true;
    }
    if ($fat !== null && $fat >= 0) {
        $product['fat'] = $fat;
        $hasData = true;
    }
    if ($carbs !== null && $carbs >= 0) {
        $product['carbs'] = $carbs;
        $hasData = true;
    }
    if ($calories !== null && $calories > 0) {
        $product['calories'] = $calories;
        $hasData = true;
    }
    
    // Удаляем поля, если данные не найдены (чтобы не было null в JSON)
    if ($weight === null || $weight <= 0) {
        unset($product['weight']);
    }
    if ($protein === null || $protein < 0) {
        unset($product['protein']);
    }
    if ($fat === null || $fat < 0) {
        unset($product['fat']);
    }
    if ($carbs === null || $carbs < 0) {
        unset($product['carbs']);
    }
    if ($calories === null || $calories <= 0) {
        unset($product['calories']);
    }
    
    if ($hasData) {
        $updated++;
        $info = [];
        if ($weight) $info[] = "вес: {$weight}";
        if ($protein) $info[] = "Б: {$protein}";
        if ($fat) $info[] = "Ж: {$fat}";
        if ($carbs) $info[] = "У: {$carbs}";
        if ($calories) $info[] = "ккал: {$calories}";
        echo "[{$index}] ✓ {$product['name']} - " . implode(', ', $info) . "\n";
    } else {
        echo "[{$index}] ⚠ {$product['name']} - данные не найдены\n";
        $notFound++;
    }
}

// Сохраняем обновленные данные
file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "\n";
echo "═══════════════════════════════════════\n";
echo "Готово!\n";
echo "Обновлено товаров: {$updated}\n";
echo "Не найдено: {$notFound}\n";
echo "Данные сохранены в: {$dataFile}\n";
echo "\n";
echo "Теперь запустите seeder на сервере:\n";
echo "php artisan db:seed --class=PirogiSeeder\n";
