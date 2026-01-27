<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Payment::where('user_id', $user->id)->with(['user', 'order']);

        // Поиск
        if ($request->has('search') && $request->get('search')) {
            $search = trim($request->get('search'));
            $query->where(function($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('payer_name', 'like', "%{$search}%")
                  ->orWhere('payer_email', 'like', "%{$search}%")
                  ->orWhere('payer_phone', 'like', "%{$search}%");
            });
        }

        // Фильтрация по статусу
        if ($request->has('status') && $request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по способу оплаты
        if ($request->has('payment_method') && $request->get('payment_method')) {
            $query->where('payment_method', $request->get('payment_method'));
        }

        // Фильтрация по заказу
        if ($request->has('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Фильтрация по дате платежа
        if ($request->has('payment_date_from')) {
            $query->whereDate('payment_date', '>=', $request->get('payment_date_from'));
        }
        if ($request->has('payment_date_to')) {
            $query->whereDate('payment_date', '<=', $request->get('payment_date_to'));
        }

        // Фильтрация по сумме
        if ($request->has('amount_from')) {
            $query->where('amount', '>=', $request->get('amount_from'));
        }
        if ($request->has('amount_to')) {
            $query->where('amount', '<=', $request->get('amount_to'));
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['payment_number', 'payer_name', 'amount', 'payment_method', 'status', 'payment_date', 'created_at', 'updated_at'];
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

        $payments = $query->paginate($perPage);

        return response()->json($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'payer_name' => 'required|string|max:255',
            'payer_email' => 'nullable|email|max:255',
            'payer_phone' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,online,other',
            'status' => 'nullable|in:pending,processing,completed,failed,refunded',
            'notes' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        // Проверяем, что заказ принадлежит пользователю (если указан)
        if (isset($validated['order_id'])) {
            $order = \App\Models\Order::find($validated['order_id']);
            if ($order && $order->user_id !== $user->id) {
                return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
            }
        }

        // Генерируем номер платежа
        $paymentNumber = 'PAY-' . strtoupper(Str::random(8));
        while (Payment::where('payment_number', $paymentNumber)->exists()) {
            $paymentNumber = 'PAY-' . strtoupper(Str::random(8));
        }

        $validated['user_id'] = $user->id;
        $validated['payment_number'] = $paymentNumber;
        $validated['payment_method'] = $validated['payment_method'] ?? 'cash';
        $validated['status'] = $validated['status'] ?? 'pending';

        $payment = Payment::create($validated);
        $payment->load(['user', 'order']);

        return response()->json($payment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что платеж принадлежит пользователю
        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $payment->load(['user', 'order']);
        return response()->json($payment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что платеж принадлежит пользователю
        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'payer_name' => 'sometimes|required|string|max:255',
            'payer_email' => 'nullable|email|max:255',
            'payer_phone' => 'nullable|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,online,other',
            'status' => 'nullable|in:pending,processing,completed,failed,refunded',
            'notes' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
        ]);

        // Проверяем, что заказ принадлежит пользователю (если указан)
        if (isset($validated['order_id'])) {
            $order = \App\Models\Order::find($validated['order_id']);
            if ($order && $order->user_id !== $user->id) {
                return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
            }
        }

        // Автоматически устанавливаем paid_at при статусе completed
        if (isset($validated['status']) && $validated['status'] === 'completed' && !isset($validated['paid_at'])) {
            $validated['paid_at'] = now();
        }

        $payment->update($validated);
        $payment->load(['user', 'order']);

        return response()->json($payment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        
        // Проверяем, что платеж принадлежит пользователю
        if ($payment->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $payment->delete();

        return response()->json(['message' => 'Платеж успешно удален']);
    }
}
