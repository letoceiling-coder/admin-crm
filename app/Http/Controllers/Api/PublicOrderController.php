<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\ShopBotUser;
use App\Services\TelegramInitDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Публичное API заказов для Mini App: по initData (telegram_id) или по телефону (fallback).
 */
class PublicOrderController extends Controller
{
    public function __construct(
        private TelegramInitDataService $initDataService
    ) {}

    /**
     * Получить init_data из запроса: заголовок X-Telegram-Init-Data или тело (init_data).
     */
    private function getInitData(Request $request): ?string
    {
        $initData = $request->header('X-Telegram-Init-Data');
        if ($initData && is_string($initData) && trim($initData) !== '') {
            return trim($initData);
        }
        $initData = $request->input('init_data');
        if ($initData && is_string($initData) && trim($initData) !== '') {
            return trim($initData);
        }
        return null;
    }

    /**
     * По init_data и shopId вернуть ShopBotUser или null; при ошибке — ответ 403/422.
     */
    private function resolveShopBotUserFromInitData(Request $request, int $shopId): ShopBotUser|JsonResponse|null
    {
        $initData = $this->getInitData($request);
        if (!$initData) {
            return null;
        }

        $shop = Shop::find($shopId);
        if (!$shop) {
            return response()->json(['message' => 'Магазин не найден'], 404);
        }

        $token = $shop->telegram_bot_token ?? '';
        if ($token === '') {
            return response()->json(['message' => 'Бот магазина не настроен'], 422);
        }

        $result = $this->initDataService->validate($initData, $token);
        if (!$result['valid']) {
            return response()->json(['message' => $result['error'] ?? 'Неверные данные'], 403);
        }

        $botUser = $this->initDataService->getOrCreateShopBotUser($shop, $result['user']);
        if (!$botUser) {
            return response()->json(['message' => 'Не удалось определить пользователя'], 403);
        }

        return $botUser;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 11 && $digits[0] === '7') {
            return $digits;
        }
        if (strlen($digits) === 10) {
            return '7' . $digits;
        }
        return $digits;
    }

    /**
     * Список заказов: по init_data (shop_bot_user_id) или по phone (fallback).
     * GET /shops/{shopId}/orders
     * Query: init_data (или заголовок X-Telegram-Init-Data) либо phone=...
     */
    public function index(Request $request, int $shopId): JsonResponse
    {
        $botUserOrError = $this->resolveShopBotUserFromInitData($request, $shopId);
        if ($botUserOrError instanceof JsonResponse) {
            return $botUserOrError;
        }
        if ($botUserOrError instanceof ShopBotUser) {
            $orders = Order::where('shop_id', $shopId)
                ->where('shop_bot_user_id', $botUserOrError->id)
                ->with(['items.product.image', 'items.product.unit'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
            return response()->json($orders);
        }

        // Fallback: по телефону
        $phone = $request->get('phone');
        if (!$phone || !is_string($phone) || trim($phone) === '') {
            return response()->json(['message' => 'Укажите init_data или телефон'], 422);
        }

        $normalized = $this->normalizePhone(trim($phone));
        if (strlen($normalized) < 10) {
            return response()->json(['message' => 'Некорректный номер телефона'], 422);
        }

        $last10 = substr($normalized, -10);
        $orders = Order::where('shop_id', $shopId)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE ?", ['%' . $last10])
            ->with(['items.product.image', 'items.product.unit'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($orders);
    }

    /**
     * Детальный просмотр заказа: по init_data проверяем shop_bot_user_id, иначе по телефону.
     * GET /shops/{shopId}/orders/{orderId}
     */
    public function show(Request $request, int $shopId, int $orderId): JsonResponse
    {
        $order = Order::where('shop_id', $shopId)->where('id', $orderId)
            ->with(['items.product.image', 'items.product.unit'])
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }

        $botUserOrError = $this->resolveShopBotUserFromInitData($request, $shopId);
        if ($botUserOrError instanceof JsonResponse) {
            return $botUserOrError;
        }
        if ($botUserOrError instanceof ShopBotUser) {
            if ((int) $order->shop_bot_user_id !== (int) $botUserOrError->id) {
                return response()->json(['message' => 'Доступ запрещён'], 403);
            }
            return response()->json($order);
        }

        // Fallback: по телефону
        $phone = $request->get('phone');
        if (!$phone || !is_string($phone) || trim($phone) === '') {
            return response()->json(['message' => 'Укажите init_data или телефон'], 422);
        }

        $normalized = $this->normalizePhone(trim($phone));
        $orderPhone = preg_replace('/\D/', '', $order->customer_phone ?? '');
        $last10 = substr($normalized, -10);
        $orderLast10 = substr($orderPhone, -10);
        if ($orderLast10 !== $last10 && $orderPhone !== $normalized) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        return response()->json($order);
    }

    /**
     * Создание заказа из Mini App (по init_data).
     * POST /shops/{shopId}/orders
     * Body: init_data, customer_name, customer_address?, notes?, total_amount, items[{product_id, quantity, price}]
     */
    public function store(Request $request, int $shopId): JsonResponse
    {
        $botUserOrError = $this->resolveShopBotUserFromInitData($request, $shopId);
        if ($botUserOrError instanceof JsonResponse) {
            return $botUserOrError;
        }
        if (!$botUserOrError instanceof ShopBotUser) {
            return response()->json(['message' => 'Укажите init_data для создания заказа'], 422);
        }

        $shop = Shop::find($shopId);
        if (!$shop) {
            return response()->json(['message' => 'Магазин не найден'], 404);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'total_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $items = $validated['items'];
        foreach ($items as $item) {
            $product = \App\Models\Product::where('id', $item['product_id'])->where('shop_id', $shopId)->first();
            if (!$product) {
                return response()->json(['message' => 'Товар не найден в этом магазине'], 422);
            }
        }

        try {
            $order = DB::transaction(function () use ($shop, $botUserOrError, $validated, $items) {
                $orderNumber = 'MA-' . str_pad((string) ($shop->id), 4, '0', STR_PAD_LEFT) . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

                $order = Order::create([
                    'user_id' => $shop->admin_id,
                    'shop_id' => $shop->id,
                    'shop_bot_user_id' => $botUserOrError->id,
                    'order_number' => $orderNumber,
                    'customer_name' => $validated['customer_name'],
                    'customer_address' => $validated['customer_address'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'total_amount' => $validated['total_amount'],
                    'status' => 'pending',
                    'order_date' => now(),
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }

                return $order->load(['items.product.image', 'items.product.unit']);
            });

            return response()->json($order, 201);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PublicOrderController::store exception', [
                'shop_id' => $shopId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Ошибка создания заказа'], 500);
        }
    }
}
