<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Получить товары по магазину (публичный доступ для Telegram Mini App)
     */
    public function getByShop(Request $request, int $shopId): JsonResponse
    {
        $query = Product::where('shop_id', $shopId)
            ->where('is_active', true)
            ->with(['category', 'unit', 'image', 'images']);

        // Фильтрация по категории
        if ($request->has('category_id')) {
            $categoryId = $request->get('category_id');
            if ($categoryId === 'null' || $categoryId === null) {
                $query->whereNull('category_id');
            } else {
                $query->where('category_id', $categoryId);
            }
        }

        // Поиск
        if ($request->has('search') && $request->get('search')) {
            $search = trim($request->get('search'));
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $query->orderBy('position', 'asc');

        // Пагинация
        $perPage = (int) $request->get('per_page', 100);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Product::where('user_id', $user->id)->with(['category', 'unit', 'image', 'images']);

        // Фильтрация по магазину
        if ($request->has('shop_id') && $request->get('shop_id')) {
            $query->where('shop_id', $request->get('shop_id'));
        }

        // Поиск
        if ($request->has('search') && $request->get('search')) {
            $search = trim($request->get('search'));
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Фильтрация по категории
        if ($request->has('category_id')) {
            $categoryId = $request->get('category_id');
            if ($categoryId === 'null' || $categoryId === null) {
                $query->whereNull('category_id');
            } else {
                $query->where('category_id', $categoryId);
            }
        }

        // Фильтрация по единице измерения
        if ($request->has('unit_id')) {
            $query->where('unit_id', $request->get('unit_id'));
        }

        // Фильтрация по активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'position');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'slug', 'sku', 'price', 'stock', 'position', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('position', 'asc');
        }

        // Пагинация
        $perPage = (int) $request->get('per_page', 20);
        $perPageOptions = [20, 30, 40, 50, 100];
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 20;
        }

        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255|unique:products,sku',
            'price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|integer|min:0',
            'protein' => 'nullable|numeric|min:0',
            'fat' => 'nullable|numeric|min:0',
            'carbs' => 'nullable|numeric|min:0',
            'calories' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'image_id' => 'nullable|exists:media,id',
            'stock' => 'nullable|integer|min:0',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'exists:media,id',
            'shop_id' => 'required|exists:shops,id',
        ]);

        // Проверяем доступ к магазину
        if (!$user->hasAccessToShop($validated['shop_id'])) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

        // Генерируем slug если не указан
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Проверяем уникальность
            $counter = 1;
            $originalSlug = $validated['slug'];
            while (Product::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        // Устанавливаем позицию по умолчанию
        if (!isset($validated['position'])) {
            $maxPosition = Product::where('user_id', $user->id)->max('position') ?? 0;
            $validated['position'] = $maxPosition + 1;
        }

        // Извлекаем images из validated
        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $validated['user_id'] = $user->id;

        DB::beginTransaction();
        try {
            $product = Product::create($validated);

            // Прикрепляем изображения
            if (!empty($images)) {
                $position = 0;
                foreach ($images as $imageId) {
                    $product->images()->attach($imageId, ['position' => $position]);
                    $position++;
                }
            }

            DB::commit();

            $product->load(['category', 'unit', 'image', 'images']);

            return response()->json($product, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при создании товара: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        
        // Если пользователь не авторизован, проверяем только активность товара (для публичного доступа)
        if (!$user) {
            if (!$product->is_active) {
                return response()->json(['message' => 'Товар не найден'], 404);
            }
            $product->load(['category', 'unit', 'image', 'images']);
            return response()->json($product);
        }
        
        // Для авторизованных пользователей проверяем доступ
        // Проверяем, что товар принадлежит пользователю
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Проверяем доступ к магазину товара
        if ($product->shop_id && !$user->hasAccessToShop($product->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

        $product->load(['category', 'unit', 'image', 'images']);
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что товар принадлежит пользователю
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Проверяем доступ к магазину товара
        if ($product->shop_id && !$user->hasAccessToShop($product->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $product->id,
            'price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|integer|min:0',
            'protein' => 'nullable|numeric|min:0',
            'fat' => 'nullable|numeric|min:0',
            'carbs' => 'nullable|numeric|min:0',
            'calories' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'image_id' => 'nullable|exists:media,id',
            'stock' => 'nullable|integer|min:0',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'exists:media,id',
            'shop_id' => 'sometimes|required|exists:shops,id',
        ]);

        // Если shop_id изменяется, проверяем доступ к новому магазину
        if (isset($validated['shop_id']) && $validated['shop_id'] !== $product->shop_id) {
            if (!$user->hasAccessToShop($validated['shop_id'])) {
                return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
            }
        }

        // Генерируем slug если не указан и изменилось имя
        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Проверяем уникальность
            $counter = 1;
            $originalSlug = $validated['slug'];
            while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        // Извлекаем images из validated
        $images = $validated['images'] ?? null;
        unset($validated['images']);

        DB::beginTransaction();
        try {
            $product->update($validated);

            // Обновляем изображения если переданы
            if ($images !== null) {
                $product->images()->detach();
                if (!empty($images)) {
                    $position = 0;
                    foreach ($images as $imageId) {
                        $product->images()->attach($imageId, ['position' => $position]);
                        $position++;
                    }
                }
            }

            DB::commit();

            $product->load(['category', 'unit', 'image', 'images']);

            return response()->json($product);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при обновлении товара: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что товар принадлежит пользователю
        if ($product->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Проверяем доступ к магазину товара
        if ($product->shop_id && !$user->hasAccessToShop($product->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Товар успешно удален']);
    }

    /**
     * Обновить позиции товаров (drag & drop)
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.position' => 'required|integer|min:0'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->products as $productData) {
                Product::where('id', $productData['id'])->update([
                    'position' => $productData['position']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Позиции товаров успешно обновлены'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления позиций: ' . $e->getMessage()
            ], 500);
        }
    }
}
