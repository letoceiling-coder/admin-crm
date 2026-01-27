<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Media;
use App\Models\Folder;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PirogiSeeder extends Seeder
{
    private $imageService;
    private $baseFolder;
    private $imageMap = [];
    private $productImageMap = [];
    private $categoryFolders = [];
    private $userId;

    public function __construct()
    {
        $this->imageService = app(ImageService::class);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Начинаем импорт данных с pirogi.ru...');

        // Определяем user_id (используем первого пользователя или создаем)
        $user = \App\Models\User::first();
        if (!$user) {
            $this->command->error('Пользователь не найден. Создайте пользователя перед запуском seeder.');
            return;
        }
        $this->userId = $user->id;
        $this->command->info("Используется пользователь ID: {$this->userId}");

        // Загружаем карту сопоставления товаров с изображениями
        $productsImagesPath = storage_path('pirogi_products_images.json');
        $this->productImageMap = [];
        if (file_exists($productsImagesPath)) {
            $productsImages = json_decode(file_get_contents($productsImagesPath), true);
            if (is_array($productsImages)) {
                foreach ($productsImages as $item) {
                    // Используем название товара как ключ (точное совпадение)
                    $this->productImageMap[$item['name']] = [
                        'imageId' => $item['imageId'],
                        'imageSlug' => $item['imageSlug'],
                        'extension' => $item['extension'],
                        'urlLarge' => "https://pirogi.ru/userfls/shop/large/{$item['imageId']}_{$item['imageSlug']}.{$item['extension']}",
                        'urlMedium' => "https://pirogi.ru/userfls/shop/medium/{$item['imageId']}_{$item['imageSlug']}.{$item['extension']}",
                    ];
                    
                    // Также добавляем по slug для резервного поиска
                    $slug = Str::slug($item['name']);
                    if (!isset($this->productImageMap[$slug])) {
                        $this->productImageMap[$slug] = $this->productImageMap[$item['name']];
                    }
                }
                $this->command->info("Загружена карта изображений: " . count($this->productImageMap) . " записей");
            }
        }
        
        // Также загружаем общую карту изображений для резервного поиска
        $imagesMapPath = storage_path('pirogi_images_map.json');
        if (file_exists($imagesMapPath)) {
            $imagesData = json_decode(file_get_contents($imagesMapPath), true);
            if (isset($imagesData['images'])) {
                foreach ($imagesData['images'] as $img) {
                    $slug = $img['slug'];
                    if (!isset($this->imageMap[$slug])) {
                        $this->imageMap[$slug] = $img;
                    }
                }
            }
        }

        // Получаем или создаем базовую папку для товаров
        $this->baseFolder = Folder::withoutGlobalScopes()
            ->where('name', 'Общая')
            ->whereNull('parent_id')
            ->first();
        if (!$this->baseFolder) {
            $this->baseFolder = Folder::create([
                'name' => 'Общая',
                'slug' => 'common',
                'src' => 'folder',
                'position' => 0,
                'user_id' => self::USER_ID,
            ]);
        }

        // Читаем данные из JSON файла
        $dataPath = storage_path('pirogi_data.json');
        if (!file_exists($dataPath)) {
            $this->command->error('Файл с данными не найден: ' . $dataPath);
            return;
        }

        $data = json_decode(file_get_contents($dataPath), true);
        
        if (!$data) {
            $this->command->error('Ошибка чтения данных из JSON файла');
            return;
        }

        // Создаем категории и папки для них
        $categoryMap = [];
        $position = 1;
        
        foreach ($data['categories'] as $catData) {
            $category = Category::withoutGlobalScopes()->firstOrCreate(
                ['slug' => Str::slug($catData['name'])],
                [
                    'name' => $catData['name'],
                    'slug' => Str::slug($catData['name']),
                    'position' => $position++,
                    'is_active' => true,
                ]
            );
            $categoryMap[$catData['id']] = $category;
            
            // Создаем папку для категории
            $categoryFolder = Folder::withoutGlobalScopes()->firstOrCreate(
                ['name' => $category->name, 'parent_id' => $this->baseFolder->id],
                [
                    'slug' => Str::slug($category->name),
                    'src' => 'folder',
                    'position' => $position - 1,
                    'user_id' => $this->userId,
                ]
            );
            $this->categoryFolders[$category->id] = $categoryFolder;
            
            $this->command->info("Создана категория: {$category->name} (папка: {$categoryFolder->name})");
        }

        // Определяем единицы измерения на основе товаров
        $unitsMap = $this->determineUnits($data['products']);
        
        // Создаем единицы измерения
        $unitModels = [];
        $unitPosition = 1;
        foreach ($unitsMap as $unitName => $unitData) {
            $unit = Unit::withoutGlobalScopes()->firstOrCreate(
                ['short_name' => $unitData['short']],
                [
                    'name' => $unitData['name'],
                    'short_name' => $unitData['short'],
                    'position' => $unitPosition++,
                    'is_active' => true,
                    'user_id' => $this->userId,
                ]
            );
            $unitModels[$unitName] = $unit;
            $this->command->info("Создана единица измерения: {$unit->name} ({$unit->short_name})");
        }

        // Создаем товары
        $productPosition = 1;
        $downloadedCount = 0;
        $failedCount = 0;
        
        foreach ($data['products'] as $productData) {
            // Определяем единицу измерения для товара
            $unit = $this->determineUnitForProduct($productData['name'], $productData['price'], $unitModels);

            // Создаем товар
            $product = Product::withoutGlobalScopes()->firstOrCreate(
                ['slug' => Str::slug($productData['name'])],
                [
                    'name' => $productData['name'],
                    'slug' => Str::slug($productData['name']),
                    'description' => null,
                    'sku' => null,
                    'price' => $productData['price'] ?? 0,
                    'category_id' => $categoryMap[$productData['categoryId']]->id ?? null,
                    'unit_id' => $unit ? $unit->id : null,
                    'stock' => 0,
                    'position' => $productPosition++,
                    'is_active' => true,
                ]
            );

            // Пытаемся найти и скачать изображение
            $imageDownloaded = $this->downloadProductImage($product, $productData, $categoryMap);
            
            if ($imageDownloaded) {
                $downloadedCount++;
                $this->command->info("Создан товар: {$product->name} ✓");
            } else {
                $failedCount++;
                $this->command->info("Создан товар: {$product->name} ⚠ (изображение не найдено)");
            }
        }

        $this->command->info('');
        $this->command->info("Импорт завершен успешно!");
        $this->command->info("Изображений скачано: {$downloadedCount}");
        $this->command->info("Изображений не найдено: {$failedCount}");
    }

    /**
     * Скачать изображение товара
     */
    private function downloadProductImage(Product $product, array $productData, array $categoryMap): bool
    {
        // Сначала ищем по точному названию товара
        $imageData = null;
        if (isset($this->productImageMap[$product->name])) {
            $imageData = $this->productImageMap[$product->name];
        } else {
            // Пробуем найти по частичному совпадению названия (без учета регистра)
            $productNameLower = mb_strtolower($product->name);
            foreach ($this->productImageMap as $name => $img) {
                $nameLower = mb_strtolower($name);
                // Точное совпадение или одно содержит другое
                if ($productNameLower === $nameLower || 
                    stripos($productNameLower, $nameLower) !== false || 
                    stripos($nameLower, $productNameLower) !== false) {
                    $imageData = $img;
                    break;
                }
            }
        }

        // Если не нашли, пробуем через slug
        if (!$imageData) {
            $productSlug = Str::slug($product->name);
            if (isset($this->productImageMap[$productSlug])) {
                $imageData = $this->productImageMap[$productSlug];
            } elseif (isset($this->imageMap[$productSlug])) {
                $img = $this->imageMap[$productSlug];
                $imageData = [
                    'extension' => $img['extension'] ?? 'jpg',
                    'urlLarge' => $img['urlLarge'] ?? null,
                    'urlMedium' => $img['urlMedium'] ?? null,
                ];
            }
        }

        if (!$imageData || (!isset($imageData['urlLarge']) && !isset($imageData['urlMedium']))) {
            return false;
        }

        // Используем large версию для лучшего качества
        $imageUrl = $imageData['urlLarge'] ?? $imageData['urlMedium'];
        
        // Получаем папку категории
        $categoryId = $product->category_id;
        $categoryFolder = $categoryId && isset($this->categoryFolders[$categoryId]) 
            ? $this->categoryFolders[$categoryId] 
            : $this->baseFolder;

        try {
            // Скачиваем изображение (отключаем проверку SSL)
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->get($imageUrl);
            
            if (!$response->successful()) {
                return false;
            }

            $imageContent = $response->body();
            $extension = $imageData['extension'] ?? 'jpg';
            
            // Сохраняем временный файл
            $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $extension;
            file_put_contents($tempPath, $imageContent);

            // Создаем медиа-запись в папке категории
            $this->createMediaFromFile($product, $tempPath, $product->name, $extension, $categoryFolder);

            // Удаляем временный файл
            @unlink($tempPath);

            return true;

        } catch (\Exception $e) {
            $this->command->warn("  Ошибка скачивания для {$product->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Определить единицы измерения на основе товаров
     */
    private function determineUnits(array $products): array
    {
        $units = [
            'portion' => ['name' => 'Порция', 'short' => 'порц.'],
            'piece' => ['name' => 'Штука', 'short' => 'шт.'],
            'kg' => ['name' => 'Килограмм', 'short' => 'кг'],
            'gram' => ['name' => 'Грамм', 'short' => 'г'],
        ];

        return $units;
    }

    /**
     * Определить единицу измерения для конкретного товара
     */
    private function determineUnitForProduct(string $name, ?float $price, array $unitModels): ?Unit
    {
        $nameLower = mb_strtolower($name);

        // Пироги
        if (strpos($nameLower, 'пирог') !== false) {
            if ($price && $price > 1000) {
                return $unitModels['kg'] ?? null;
            }
            return $unitModels['piece'] ?? null;
        }

        // Блины, слойки, выпечка - поштучно
        if (strpos($nameLower, 'блин') !== false || 
            strpos($nameLower, 'слойка') !== false ||
            strpos($nameLower, 'круассан') !== false ||
            strpos($nameLower, 'маффин') !== false ||
            strpos($nameLower, 'флан') !== false ||
            strpos($nameLower, 'завиток') !== false ||
            strpos($nameLower, 'плюха') !== false ||
            strpos($nameLower, 'ромовая баба') !== false) {
            return $unitModels['piece'] ?? null;
        }

        // Салаты, супы, вторые блюда, гарниры - порциями
        if (strpos($nameLower, 'салат') !== false ||
            strpos($nameLower, 'суп') !== false ||
            strpos($nameLower, 'борщ') !== false ||
            strpos($nameLower, 'солянка') !== false ||
            strpos($nameLower, 'бульон') !== false ||
            strpos($nameLower, 'котлета') !== false ||
            strpos($nameLower, 'гарнир') !== false ||
            strpos($nameLower, 'пюре') !== false ||
            strpos($nameLower, 'рис') !== false ||
            strpos($nameLower, 'греча') !== false ||
            strpos($nameLower, 'паста') !== false) {
            return $unitModels['portion'] ?? null;
        }

        // По умолчанию - порция
        return $unitModels['portion'] ?? null;
    }

    /**
     * Создать медиа-запись из файла
     */
    private function createMediaFromFile(Product $product, string $filePath, string $originalName, string $extension, Folder $folder): void
    {
        try {
            $fileSize = filesize($filePath);
            $mimeType = mime_content_type($filePath);
            
            // Определяем тип файла
            $type = 'photo';
            if (strpos($mimeType, 'image') === false) {
                return; // Пропускаем не-изображения
            }

            // Генерируем уникальное имя файла
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            
            // Определяем путь для сохранения (в папке категории)
            $folderPath = $this->getFolderPath($folder);
            $uploadPath = 'upload/' . $folderPath;
            $fullPath = public_path($uploadPath);
            
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Копируем файл
            $targetPath = $fullPath . '/' . $fileName;
            copy($filePath, $targetPath);

            // Обрабатываем изображение
            $imageVariants = null;
            $width = null;
            $height = null;

            if ($type === 'photo') {
                try {
                    $imageVariants = $this->imageService->processImage(
                        $targetPath,
                        $extension,
                        $uploadPath,
                        $baseName
                    );

                    $imageInfo = @getimagesize($targetPath);
                    if ($imageInfo !== false) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                } catch (\Exception $e) {
                    $this->command->warn("  Ошибка обработки изображения для {$product->name}: " . $e->getMessage());
                }
            }

            // Сохраняем метаданные
            $metadata = [
                'path' => $uploadPath . '/' . $fileName,
                'mime_type' => $mimeType,
            ];

            if ($imageVariants) {
                $metadata['webp_path'] = $imageVariants['webp'] ?? null;
                $metadata['variants'] = $imageVariants['variants'] ?? [];
            }

            // Создаем запись в БД
            $media = Media::create([
                'name' => $fileName,
                'original_name' => $originalName . '.' . $extension,
                'extension' => $extension,
                'disk' => $uploadPath,
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'size' => $fileSize,
                'folder_id' => $folder->id,
                'user_id' => $this->userId,
                'temporary' => false,
                'metadata' => json_encode($metadata),
            ]);

            // Привязываем к товару
            $product->update(['image_id' => $media->id]);
            $product->images()->attach($media->id, ['position' => 0]);

        } catch (\Exception $e) {
            $this->command->warn("  Ошибка создания медиа для {$product->name}: " . $e->getMessage());
        }
    }

    /**
     * Получить путь папки (рекурсивно)
     */
    private function getFolderPath(Folder $folder): string
    {
        $path = [$folder->slug];
        $parent = $folder->parent;
        
        while ($parent) {
            array_unshift($path, $parent->slug);
            $parent = $parent->parent;
        }
        
        return implode('/', $path);
    }
}
