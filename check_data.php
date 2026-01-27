<?php

$data = json_decode(file_get_contents('storage/pirogi_data.json'), true);

$withAll = 0;
$withSome = 0;
$missing = [];

foreach($data['products'] as $p) {
    $hasWeight = isset($p['weight']);
    $hasProtein = isset($p['protein']);
    $hasFat = isset($p['fat']);
    $hasCarbs = isset($p['carbs']);
    $hasCalories = isset($p['calories']);
    
    if($hasWeight && $hasProtein && $hasFat && $hasCarbs && $hasCalories) {
        $withAll++;
    }
    if($hasWeight || $hasProtein) {
        $withSome++;
    }
    
    if(!$hasWeight || !$hasProtein || !$hasFat || !$hasCarbs || !$hasCalories) {
        $missing[] = $p['name'];
    }
}

echo "С полными данными: {$withAll}\n";
echo "С частичными данными: {$withSome}\n";
echo "Всего товаров: " . count($data['products']) . "\n";

if(count($missing) > 0) {
    echo "\nТовары без полных данных:\n";
    foreach($missing as $name) {
        echo "  - {$name}\n";
    }
} else {
    echo "\n✅ Все товары имеют полные данные!\n";
}
