<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethodSetting;
use App\Services\Payment\YooKassaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Payment::query()->with(['user', 'order']);

        // Фильтрация по магазину
        if ($request->has('shop_id') && $request->get('shop_id')) {
            $shopId = (int) $request->get('shop_id');
            if (!$user->hasAccessToShop($shopId)) {
                return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
            }
            $query->where('shop_id', $shopId);
        } else {
            // Без shop_id: ограничиваем платежи только теми, к каким есть доступ через магазины
            if ($user->isDeveloper()) {
                // developer видит все
            } else {
                // admin -> только свои магазины, manager -> назначенные
                $accessibleShopIds = $user->isAdmin()
                    ? $user->ownedShops()->pluck('id')
                    : ($user->isManager() ? $user->shops()->pluck('shops.id') : collect());
                $query->whereIn('shop_id', $accessibleShopIds);
            }
        }

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
            'shop_id' => 'required|exists:shops,id',
        ]);

        // Проверяем доступ к магазину
        if (!$user->hasAccessToShop($validated['shop_id'])) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

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
        if ($payment->shop_id && !$user->hasAccessToShop((int) $payment->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
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
        if ($payment->shop_id && !$user->hasAccessToShop((int) $payment->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
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
            'shop_id' => 'sometimes|required|exists:shops,id',
        ]);

        // Если shop_id изменяется, проверяем доступ к новому магазину
        if (isset($validated['shop_id']) && $validated['shop_id'] !== $payment->shop_id) {
            if (!$user->hasAccessToShop($validated['shop_id'])) {
                return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
            }
        }

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
        if ($payment->shop_id && !$user->hasAccessToShop((int) $payment->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }

        $payment->delete();

        return response()->json(['message' => 'Платеж успешно удален']);
    }

    private function getYooKassaSettingForShop(int $shopId): ?PaymentMethodSetting
    {
        return PaymentMethodSetting::whereNull('user_id')
            ->where('shop_id', $shopId)
            ->where('payment_method_code', PaymentMethodSetting::CODE_YOOKASSA)
            ->first();
    }

    /**
     * Ручной capture YooKassa (админка), привязано к shop_id платежа
     * POST /admin/payments/{payment}/yookassa/capture
     */
    public function yooKassaCapture(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        if (!$payment->shop_id || !$user->hasAccessToShop($payment->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }
        if (($payment->payment_provider ?? null) !== 'yookassa') {
            return response()->json(['message' => 'Платеж не относится к ЮКасса'], 422);
        }
        if (!$payment->transaction_id) {
            return response()->json(['message' => 'Отсутствует transaction_id (payment_id ЮКасса)'], 422);
        }

        $pm = $this->getYooKassaSettingForShop((int) $payment->shop_id);
        if (!$pm || !$pm->is_enabled) {
            return response()->json(['message' => 'ЮКасса не подключена для этого магазина'], 422);
        }

        try {
            $service = new YooKassaService($pm);
            $captured = $service->capturePayment($payment->transaction_id);

            DB::transaction(function () use ($payment, $captured) {
                $payload = $payment->provider_payload ?? [];
                $payload['capture'][] = [
                    'at' => now()->toISOString(),
                    'response' => $captured,
                ];
                $payment->provider_payload = $payload;

                $status = $captured['status'] ?? null;
                if ($status === 'succeeded') {
                    $payment->status = 'completed';
                    $payment->paid_at = $payment->paid_at ?? now();
                } elseif ($status === 'canceled') {
                    $payment->status = 'failed';
                } else {
                    $payment->status = 'processing';
                }
                $payment->save();

                if ($payment->order_id && $payment->status === 'completed') {
                    $order = Order::find($payment->order_id);
                    if ($order && $order->status === 'pending') {
                        $order->status = 'processing';
                        $order->save();
                    }
                }
            });

            $payment->load(['user', 'order']);
            return response()->json(['data' => $payment]);
        } catch (\Throwable $e) {
            Log::error('yooKassaCapture error', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Ошибка capture: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Возврат YooKassa (полный или частичный) (админка), привязано к shop_id платежа
     * POST /admin/payments/{payment}/yookassa/refund
     * Body: amount? (если не указан — полный возврат)
     */
    public function yooKassaRefund(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        if (!$payment->shop_id || !$user->hasAccessToShop($payment->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }
        if (($payment->payment_provider ?? null) !== 'yookassa') {
            return response()->json(['message' => 'Платеж не относится к ЮКасса'], 422);
        }
        if (!$payment->transaction_id) {
            return response()->json(['message' => 'Отсутствует transaction_id (payment_id ЮКасса)'], 422);
        }

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = isset($validated['amount']) ? (float) $validated['amount'] : (float) $payment->amount;
        $paymentAmount = (float) $payment->amount;
        if ($amount > $paymentAmount) {
            return response()->json(['message' => 'Сумма возврата не может превышать сумму платежа'], 422);
        }

        $pm = $this->getYooKassaSettingForShop((int) $payment->shop_id);
        if (!$pm || !$pm->is_enabled) {
            return response()->json(['message' => 'ЮКасса не подключена для этого магазина'], 422);
        }

        try {
            $service = new YooKassaService($pm);
            $refund = $service->createRefund($payment->transaction_id, [
                'amount' => $amount,
                'currency' => 'RUB',
                'description' => $validated['description'] ?? ('Возврат по платежу ' . $payment->payment_number),
            ]);

            DB::transaction(function () use ($payment, $refund, $amount, $paymentAmount) {
                $payload = $payment->provider_payload ?? [];
                $payload['refunds'][] = [
                    'at' => now()->toISOString(),
                    'amount' => $amount,
                    'response' => $refund,
                ];
                $payment->provider_payload = $payload;

                if (($refund['status'] ?? null) === 'succeeded') {
                    // Полный возврат -> статус refunded, частичный -> оставляем completed, но фиксируем в notes
                    if (abs($amount - $paymentAmount) < 0.01) {
                        $payment->status = 'refunded';
                    } else {
                        $payment->status = $payment->status === 'failed' ? 'failed' : 'completed';
                        $payment->notes = trim((string) ($payment->notes ?? '') . "\nЧастичный возврат: " . number_format($amount, 2, '.', ''));
                    }
                }
                $payment->save();
            });

            $payment->load(['user', 'order']);
            return response()->json(['data' => $payment]);
        } catch (\Throwable $e) {
            Log::error('yooKassaRefund error', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Ошибка возврата: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Принудительно обновить статус платежа из ЮКасса (GET payment по transaction_id).
     * POST /admin/payments/{payment}/yookassa/sync
     */
    public function yooKassaSync(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        if (!$payment->shop_id || !$user->hasAccessToShop($payment->shop_id)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }
        if (($payment->payment_provider ?? null) !== 'yookassa') {
            return response()->json(['message' => 'Платеж не относится к ЮКасса'], 422);
        }
        if (!$payment->transaction_id) {
            return response()->json(['message' => 'Отсутствует transaction_id (payment_id ЮКасса)'], 422);
        }

        $pm = $this->getYooKassaSettingForShop((int) $payment->shop_id);
        if (!$pm || !$pm->is_enabled) {
            return response()->json(['message' => 'ЮКасса не подключена для этого магазина'], 422);
        }

        try {
            $service = new YooKassaService($pm);
            $ykPayment = $service->getPayment($payment->transaction_id);

            $status = $ykPayment['status'] ?? null;
            $crmStatus = $payment->status;
            if ($status === 'succeeded') {
                $crmStatus = 'completed';
            } elseif ($status === 'canceled') {
                $crmStatus = 'failed';
            } elseif ($status === 'waiting_for_capture') {
                $crmStatus = 'processing';
            } elseif ($status === 'pending') {
                $crmStatus = 'pending';
            }
            // refunded в CRM не меняем по GET payment — только по webhook refund.succeeded
            if ($payment->status === 'refunded') {
                $crmStatus = 'refunded';
            }

            DB::transaction(function () use ($payment, $ykPayment, $crmStatus) {
                $payload = $payment->provider_payload ?? [];
                $payload['last_sync'] = [
                    'at' => now()->toISOString(),
                    'response' => $ykPayment,
                ];
                $payment->provider_payload = $payload;
                $payment->status = $crmStatus;
                if ($crmStatus === 'completed' && !$payment->paid_at) {
                    $payment->paid_at = now();
                }
                $payment->save();

                if ($payment->order_id && $crmStatus === 'completed') {
                    $order = Order::find($payment->order_id);
                    if ($order && $order->status === 'pending') {
                        $order->status = 'processing';
                        $order->save();
                    }
                }
            });

            $payment->load(['user', 'order']);
            return response()->json(['data' => $payment]);
        } catch (\Throwable $e) {
            Log::error('yooKassaSync error', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Ошибка синхронизации: ' . $e->getMessage()], 500);
        }
    }
}
