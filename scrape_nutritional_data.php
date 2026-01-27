<?php

/**
 * Скрипт для сбора питательных веществ и веса товаров с pirogi.ru
 * 
 * Использование:
 * php scrape_nutritional_data.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

// Загружаем существующие данные
$dataFile = __DIR__ . '/storage/pirogi_data.json';
if (!file_exists($dataFile)) {
    die("Файл {$dataFile} не найден!\n");
}

$data = json_decode(file_get_contents($dataFile), true);
if (!$data || !isset($data['products'])) {
    die("Неверный формат данных в {$dataFile}\n");
}

echo "Найдено товаров: " . count($data['products']) . "\n";
echo "Начинаем сбор детальной информации...\n\n";

$baseUrl = 'https://pirogi.ru/';
$updated = 0;
$errors = 0;
$skipped = 0;

foreach ($data['products'] as $index => &$product) {
    $productName = $product['name'];
    echo "[" . ($index + 1) . "/" . count($data['products']) . "] {$productName}... ";
    
    // Пропускаем, если данные уже есть
    if (isset($product['weight']) || isset($product['protein'])) {
        echo "⏭ (уже есть данные)\n";
        $skipped++;
        continue;
    }
    
    try {
        // Пробуем найти товар через поиск или категорию
        // Формируем URL для поиска товара
        $searchQuery = urlencode($productName);
        
        // Пробуем загрузить главную страницу категории
        $categorySlug = '';
        $categoryMap = [
            'Салаты' => 'salaty',
            'Супы' => 'supy',
            'Вторые блюда' => 'vtorye-blyuda',
            'Гарниры' => 'garniry',
            'Пироги сытные' => 'pirogi-sytnye',
            'Пироги сладкие' => 'pirogi-sladkie',
            'Выпечка' => 'vypechka',
            'Блины' => 'bliny',
        ];
        
        if (isset($categoryMap[$product['categoryName']])) {
            $categorySlug = $categoryMap[$product['categoryName']];
        }
        
        // Пробуем загрузить страницу категории
        $categoryUrl = $baseUrl . ($categorySlug ? $categorySlug . '/' : '');
        
        $response = Http::timeout(30)
            ->withoutVerifying()
            ->get($categoryUrl);
        
        if (!$response->successful()) {
            echo "⚠ (не удалось загрузить страницу)\n";
            $errors++;
            continue;
        }
        
        $html = $response->body();
        
        // Парсим HTML для поиска товара
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Ищем ссылку на товар по названию (ищем в тексте ссылок)
        $productLink = null;
        
        // Пробуем найти по точному названию
        $links = $xpath->query("//a[contains(., '" . htmlspecialchars($productName, ENT_QUOTES) . "')]");
        
        if ($links->length === 0) {
            // Пробуем найти по части названия
            $nameParts = explode(' ', $productName);
            if (count($nameParts) > 1) {
                $firstPart = $nameParts[0];
                $links = $xpath->query("//a[contains(., '" . htmlspecialchars($firstPart, ENT_QUOTES) . "')]");
            }
        }
        
        if ($links->length > 0) {
            $productLink = $links->item(0)->getAttribute('href');
            if ($productLink && !preg_match('/^https?:\/\//', $productLink)) {
                $productLink = $baseUrl . ltrim($productLink, '/');
            }
        }
        
        if (!$productLink) {
            echo "⚠ (ссылка не найдена)\n";
            $errors++;
            continue;
        }
        
        // Загружаем страницу товара
        $productResponse = Http::timeout(30)
            ->withoutVerifying()
            ->get($productLink);
        
        if (!$productResponse->successful()) {
            echo "⚠ (не удалось загрузить страницу товара)\n";
            $errors++;
            continue;
        }
        
        $productHtml = $productResponse->body();
        
        // Парсим страницу товара
        $productDom = new DOMDocument();
        @$productDom->loadHTML('<?xml encoding="UTF-8">' . $productHtml);
        $productXpath = new DOMXPath($productDom);
        
        // Извлекаем вес порции: <div class="id-weight">250 гр</div>
        $weight = null;
        $weightNodes = $productXpath->query("//div[contains(@class, 'id-weight')]");
        if ($weightNodes->length > 0) {
            $weightText = trim($weightNodes->item(0)->textContent);
            if (preg_match('/(\d+)\s*гр?/iu', $weightText, $matches)) {
                $weight = (int)$matches[1];
            }
        }
        
        // Извлекаем питательные вещества
        $protein = null;
        $fat = null;
        $carbs = null;
        $calories = null;
        
        $paramItems = $productXpath->query("//div[contains(@class, 'id-params-item')]");
        foreach ($paramItems as $item) {
            $titleNodes = $productXpath->query(".//div[contains(@class, 'title')]", $item);
            $valueNodes = $productXpath->query(".//div[contains(@class, 'value')]", $item);
            
            if ($titleNodes->length > 0 && $valueNodes->length > 0) {
                $title = trim($titleNodes->item(0)->textContent);
                $valueText = trim($valueNodes->item(0)->textContent);
                
                // Убираем теги <small>
                $valueText = preg_replace('/<small>.*?<\/small>/i', '', $valueText);
                $valueText = trim(strip_tags($valueText));
                
                if (preg_match('/([\d.]+)/', $valueText, $matches)) {
                    $value = (float)$matches[1];
                    
                    $titleLower = mb_strtolower($title);
                    if (mb_stripos($titleLower, 'белк') !== false) {
                        $protein = $value;
                    } elseif (mb_stripos($titleLower, 'жир') !== false) {
                        $fat = $value;
                    } elseif (mb_stripos($titleLower, 'углевод') !== false) {
                        $carbs = $value;
                    } elseif (mb_stripos($titleLower, 'калори') !== false) {
                        $calories = (int)$value;
                    }
                }
            }
        }
        
        // Обновляем данные товара
        if ($weight !== null || $protein !== null || $fat !== null || $carbs !== null || $calories !== null) {
            if ($weight !== null) $product['weight'] = $weight;
            if ($protein !== null) $product['protein'] = $protein;
            if ($fat !== null) $product['fat'] = $fat;
            if ($carbs !== null) $product['carbs'] = $carbs;
            if ($calories !== null) $product['calories'] = $calories;
            
            $updated++;
            $info = [];
            if ($weight) $info[] = "вес: {$weight}г";
            if ($protein) $info[] = "Б: {$protein}";
            if ($fat) $info[] = "Ж: {$fat}";
            if ($carbs) $info[] = "У: {$carbs}";
            if ($calories) $info[] = "ккал: {$calories}";
            echo "✓ (" . implode(', ', $info) . ")\n";
        } else {
            echo "⚠ (данные не найдены)\n";
            $errors++;
        }
        
        // Небольшая задержка
        usleep(500000); // 0.5 секунды
        
    } catch (\Exception $e) {
        echo "✗ (ошибка: " . $e->getMessage() . ")\n";
        $errors++;
    }
}

// Сохраняем обновленные данные
file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "\n";
echo "═══════════════════════════════════════\n";
echo "Готово!\n";
echo "Обновлено товаров: {$updated}\n";
echo "Пропущено (уже есть данные): {$skipped}\n";
echo "Ошибок: {$errors}\n";
echo "Данные сохранены в: {$dataFile}\n";
echo "\n";
echo "Теперь запустите seeder для обновления БД:\n";
echo "php artisan db:seed --class=PirogiSeeder\n";
