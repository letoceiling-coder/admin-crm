<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Delivery::where('user_id', $user->id)->with(['user', 'order']);

        // Поиск
        if ($request->has('search') && $request->get('search')) {
            $search = trim($request->get('search'));
            $query->where(function($q) use ($search) {
                $q->where('delivery_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%");
            });
        }

        // Фильтрация по статусу
        if ($request->has('status') && $request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по заказу
        if ($request->has('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Фильтрация по дате доставки
        if ($request->has('delivery_date_from')) {
            $query->whereDate('delivery_date', '>=', $request->get('delivery_date_from'));
        }
        if ($request->has('delivery_date_to')) {
            $query->whereDate('delivery_date', '<=', $request->get('delivery_date_to'));
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['delivery_number', 'recipient_name', 'status', 'delivery_date', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Пагинация
        $perPage = (int) $request->get('per_page', 20);
        $perPageOptions = [20, 30, 40, 50, 100];
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 20;
        }

        $deliveries = $query->paginate($perPage);

        return response()->json($deliveries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:255',
            'delivery_address' => 'required|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,in_transit,delivered,cancelled',
            'delivery_date' => 'nullable|date',
        ]);

        // Проверяем, что заказ принадлежит пользователю (если указан)
        if (isset($validated['order_id'])) {
            $order = \App\Models\Order::find($validated['order_id']);
            if ($order && $order->user_id !== $user->id) {
                return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
            }
        }

        // Генерируем номер доставки
        $deliveryNumber = 'DEL-' . strtoupper(Str::random(8));
        while (Delivery::where('delivery_number', $deliveryNumber)->exists()) {
            $deliveryNumber = 'DEL-' . strtoupper(Str::random(8));
        }

        $validated['user_id'] = $user->id;
        $validated['delivery_number'] = $deliveryNumber;
        $validated['status'] = $validated['status'] ?? 'pending';

        $delivery = Delivery::create($validated);
        $delivery->load(['user', 'order']);

        return response()->json($delivery, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Delivery $delivery): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что доставка принадлежит пользователю
        if ($delivery->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $delivery->load(['user', 'order']);
        return response()->json($delivery);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Delivery $delivery): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что доставка принадлежит пользователю
        if ($delivery->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'recipient_name' => 'sometimes|required|string|max:255',
            'recipient_phone' => 'sometimes|required|string|max:255',
            'delivery_address' => 'sometimes|required|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,in_transit,delivered,cancelled',
            'delivery_date' => 'nullable|date',
            'delivered_at' => 'nullable|date',
        ]);

        // Проверяем, что заказ принадлежит пользователю (если указан)
        if (isset($validated['order_id'])) {
            $order = \App\Models\Order::find($validated['order_id']);
            if ($order && $order->user_id !== $user->id) {
                return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
            }
        }

        // Автоматически устанавливаем delivered_at при статусе delivered
        if (isset($validated['status']) && $validated['status'] === 'delivered' && !isset($validated['delivered_at'])) {
            $validated['delivered_at'] = now();
        }

        $delivery->update($validated);
        $delivery->load(['user', 'order']);

        return response()->json($delivery);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Delivery $delivery): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что доставка принадлежит пользователю
        if ($delivery->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $delivery->delete();

        return response()->json(['message' => 'Доставка успешно удалена']);
    }
}
