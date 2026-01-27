<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Order::where('user_id', $user->id)->with(['user', 'deliveries', 'payments']);

        // Поиск
        if ($request->has('search') && $request->get('search')) {
            $search = trim($request->get('search'));
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Фильтрация по статусу
        if ($request->has('status') && $request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по дате заказа
        if ($request->has('order_date_from')) {
            $query->whereDate('order_date', '>=', $request->get('order_date_from'));
        }
        if ($request->has('order_date_to')) {
            $query->whereDate('order_date', '<=', $request->get('order_date_to'));
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['order_number', 'customer_name', 'total_amount', 'status', 'order_date', 'created_at', 'updated_at'];
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

        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:pending,processing,completed,cancelled',
            'order_date' => 'nullable|date',
        ]);

        // Генерируем номер заказа
        $orderNumber = 'ORD-' . strtoupper(Str::random(8));
        while (Order::where('order_number', $orderNumber)->exists()) {
            $orderNumber = 'ORD-' . strtoupper(Str::random(8));
        }

        $validated['user_id'] = $user->id;
        $validated['order_number'] = $orderNumber;
        $validated['status'] = $validated['status'] ?? 'pending';

        $order = Order::create($validated);
        $order->load(['user', 'deliveries', 'payments']);

        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что заказ принадлежит пользователю
        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $order->load(['user', 'deliveries', 'payments']);
        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что заказ принадлежит пользователю
        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $validated = $request->validate([
            'customer_name' => 'sometimes|required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'status' => 'nullable|in:pending,processing,completed,cancelled',
            'order_date' => 'nullable|date',
        ]);

        $order->update($validated);
        $order->load(['user', 'deliveries', 'payments']);

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что заказ принадлежит пользователю
        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $order->delete();

        return response()->json(['message' => 'Заказ успешно удален']);
    }
}
