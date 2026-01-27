<?php

/**
 * Скрипт для сбора всех питательных веществ через браузер MCP
 * Использование: Запускается автоматически через браузерные инструменты
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dataFile = __DIR__ . '/storage/pirogi_data.json';
$data = json_decode(file_get_contents($dataFile), true);

echo "Начинаем сбор данных для " . count($data['products']) . " товаров...\n";
echo "Этот скрипт требует использования браузерных инструментов MCP.\n";
echo "Используйте HTML скрипт scrape_nutritional_data_local.html для ручного сбора.\n\n";

// Показываем пример данных для первого товара
$firstProduct = $data['products'][0];
echo "Пример для товара: {$firstProduct['name']}\n";
echo "Данные должны быть в формате:\n";
echo "{\n";
echo "  \"weight\": 200,\n";
echo "  \"protein\": 9.6,\n";
echo "  \"fat\": 4.5,\n";
echo "  \"carbs\": 25,\n";
echo "  \"calories\": 180\n";
echo "}\n\n";

echo "Используйте scrape_nutritional_data_local.html для сбора всех данных.\n";
