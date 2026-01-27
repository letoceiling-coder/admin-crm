<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Category::where('user_id', $user->id)->with(['parent', 'image']);

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
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Фильтрация по родительской категории
        if ($request->has('parent_id')) {
            $parentId = $request->get('parent_id');
            if ($parentId === 'null' || $parentId === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        // Фильтрация по активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'position');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'slug', 'position', 'created_at', 'updated_at'];
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

        $categories = $query->paginate($perPage);

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image_id' => 'nullable|exists:media,id',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
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
            while (Category::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        // Устанавливаем позицию по умолчанию
        if (!isset($validated['position'])) {
            $maxPosition = Category::where('user_id', $user->id)
                ->where('parent_id', $validated['parent_id'] ?? null)
                ->max('position') ?? 0;
            $validated['position'] = $maxPosition + 1;
        }

        $validated['user_id'] = $user->id;
        
        $category = Category::create($validated);
        $category->load(['parent', 'image']);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Category $category): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что категория принадлежит пользователю
        if ($category->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Проверяем доступ к магазину категории
        if ($category->shop_id && !$user->hasAccessToShop($category->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

        $category->load(['parent', 'image', 'children', 'products']);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что категория принадлежит пользователю
        if ($category->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Проверяем доступ к магазину категории
        if ($category->shop_id && !$user->hasAccessToShop($category->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image_id' => 'nullable|exists:media,id',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'shop_id' => 'sometimes|required|exists:shops,id',
        ]);

        // Если shop_id изменяется, проверяем доступ к новому магазину
        if (isset($validated['shop_id']) && $validated['shop_id'] !== $category->shop_id) {
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
            while (Category::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $category->update($validated);
        $category->load(['parent', 'image']);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что категория принадлежит пользователю
        if ($category->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        // Проверяем доступ к магазину категории
        if ($category->shop_id && !$user->hasAccessToShop($category->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

        // Проверяем, есть ли дочерние категории или товары
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Невозможно удалить категорию, так как у неё есть дочерние категории'
            ], 422);
        }

        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Невозможно удалить категорию, так как в ней есть товары'
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Категория успешно удалена']);
    }

    /**
     * Обновить позиции категорий (drag & drop)
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.position' => 'required|integer|min:0'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->categories as $categoryData) {
                Category::where('id', $categoryData['id'])->update([
                    'position' => $categoryData['position']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Позиции категорий успешно обновлены'
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
