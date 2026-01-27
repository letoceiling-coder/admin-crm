<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Unit::where('user_id', $user->id);

        // Поиск
        if ($request->has('search') && $request->get('search')) {
            $search = trim($request->get('search'));
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        // Фильтрация по активности
        if ($request->has('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'position');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'short_name', 'position', 'created_at', 'updated_at'];
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

        $units = $query->paginate($perPage);

        return response()->json($units);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Устанавливаем позицию по умолчанию
        if (!isset($validated['position'])) {
            $maxPosition = Unit::where('user_id', $user->id)->max('position') ?? 0;
            $validated['position'] = $maxPosition + 1;
        }

        $validated['user_id'] = $user->id;
        $unit = Unit::create($validated);

        return response()->json($unit, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Unit $unit): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что единица измерения принадлежит пользователю
        if ($unit->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        return response()->json($unit);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что единица измерения принадлежит пользователю
        if ($unit->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'short_name' => 'sometimes|required|string|max:50',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $unit->update($validated);

        return response()->json($unit);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        // Проверяем, есть ли товары с этой единицей измерения
        if ($unit->products()->count() > 0) {
            return response()->json([
                'message' => 'Невозможно удалить единицу измерения, так как она используется в товарах'
            ], 422);
        }

        $unit->delete();

        return response()->json(['message' => 'Единица измерения успешно удалена']);
    }

    /**
     * Обновить позиции единиц измерения (drag & drop)
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate([
            'units' => 'required|array',
            'units.*.id' => 'required|exists:units,id',
            'units.*.position' => 'required|integer|min:0'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->units as $unitData) {
                // Проверяем, что единица измерения принадлежит пользователю
                $unit = Unit::find($unitData['id']);
                if ($unit && $unit->user_id === $user->id) {
                    $unit->update([
                        'position' => $unitData['position']
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Позиции единиц измерения успешно обновлены'
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
