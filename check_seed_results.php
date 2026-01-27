<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Проверка результатов импорта ===\n\n";

echo "Категории: " . \App\Models\Category::count() . "\n";
echo "Товары: " . \App\Models\Product::count() . "\n";
echo "Товары с изображениями: " . \App\Models\Product::whereNotNull('image_id')->count() . "\n";
echo "Единицы измерения: " . \App\Models\Unit::count() . "\n";
echo "Папки категорий: " . \App\Models\Folder::where('parent_id', 1)->count() . "\n";
echo "Медиа файлы: " . \App\Models\Media::where('user_id', 13)->count() . "\n\n";

echo "Медиа файлы по папкам категорий:\n";
\App\Models\Folder::where('parent_id', 1)->with('files')->get()->each(function($f) {
    echo "  {$f->name}: {$f->files->count()} файлов\n";
});

echo "\nПримеры товаров с изображениями:\n";
\App\Models\Product::with(['image', 'category'])->whereNotNull('image_id')->limit(5)->get()->each(function($p) {
    $imageInfo = $p->image ? "✓ Фото: {$p->image->name}" : "✗ Нет фото";
    $catName = $p->category ? $p->category->name : 'без категории';
    echo "  {$p->name} ({$catName}) - {$imageInfo}\n";
});
