<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodSettingsRequest;
use App\Models\PaymentMethodSetting;
use App\Services\Payment\YooKassaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentMethodSettingsController extends Controller
{
    /**
     * Получить настройки способов оплаты для текущего пользователя и магазина (админка)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $request->input('shop_id');
        
        // shop_id обязателен для админки
        if (!$shopId) {
            return response()->json(['message' => 'shop_id обязателен'], 400);
        }
        
        // Проверяем доступ к магазину
        if (!$user->hasAccessToShop($shopId)) {
            return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
        }
        
        try {
            // Загружаем настройки уровня магазина (user_id = null), чтобы админка и фронт видели одни и те же данные
            $settings = PaymentMethodSetting::getSettings(null, $shopId);
            
            return response()->json([
                'data' => $settings->map(function ($setting) {
                    $data = $setting->toArray();
                    $data['name'] = $setting->getName();
                    $data['description'] = $setting->getDescription();
                    if ($setting->payment_method_code === PaymentMethodSetting::CODE_YOOKASSA) {
                        $data['yookassa_integration'] = $setting->getYooKassaConfigForApi();
                        if ($setting->shop_id) {
                            $data['yookassa_integration']['yookassa_webhook_suggested_url'] = rtrim(config('app.url'), '/') . '/api/shops/' . (int) $setting->shop_id . '/webhooks/yookassa';
                        }
                    }
                    return $data;
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading payment method settings', [
                'user_id' => $user->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при загрузке способов оплаты',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить настройки способа оплаты (админка)
     * 
     * @param PaymentMethodSettingsRequest $request
     * @param string $code
     * @return JsonResponse
     */
    public function update(PaymentMethodSettingsRequest $request, string $code): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $user = $request->user();
            $shopId = $request->input('shop_id');
            
            // shop_id обязателен для админки
            if (!$shopId) {
                DB::rollBack();
                return response()->json(['message' => 'shop_id обязателен'], 400);
            }
            
            // Проверяем доступ к магазину
            if (!$user->hasAccessToShop($shopId)) {
                DB::rollBack();
                return response()->json(['message' => 'Доступ к магазину запрещен'], 403);
            }
            
            // Проверяем валидность кода способа оплаты
            $validCodes = [PaymentMethodSetting::CODE_CASH, PaymentMethodSetting::CODE_YOOKASSA];
            if (!in_array($code, $validCodes)) {
                DB::rollBack();
                return response()->json(['message' => 'Неверный код способа оплаты'], 400);
            }
            
            $validated = $request->validated();
            // Явно берём доступность из запроса, чтобы false и отсутствующие ключи всегда сохранялись
            $validated['available_for_delivery'] = $request->boolean('available_for_delivery');
            $validated['available_for_pickup'] = $request->boolean('available_for_pickup');
            $validated['is_enabled'] = $request->boolean('is_enabled');
            $validated['is_default'] = $request->boolean('is_default');
            $validated['show_notification'] = $request->boolean('show_notification');

            // Получаем или создаем настройки уровня магазина (user_id = null), чтобы фронт чекаута видел те же данные
            $setting = PaymentMethodSetting::whereNull('user_id')
                ->where('shop_id', $shopId)
                ->where('payment_method_code', $code)
                ->first();
            
            if (!$setting) {
                // Создаем настройки по умолчанию для магазина
                $defaults = [
                    PaymentMethodSetting::CODE_CASH => [
                        'name' => 'Наличные',
                        'description' => 'Оплата наличными при получении',
                        'is_enabled' => true,
                        'available_for_delivery' => true,
                        'available_for_pickup' => true,
                        'sort_order' => 2,
                        'discount_type' => PaymentMethodSetting::DISCOUNT_TYPE_NONE,
                        'is_default' => true,
                    ],
                    PaymentMethodSetting::CODE_YOOKASSA => [
                        'name' => 'ЮКасса',
                        'description' => 'Оплата картой через ЮКассу',
                        'is_enabled' => true,
                        'available_for_delivery' => true,
                        'available_for_pickup' => false,
                        'sort_order' => 1,
                        'discount_type' => PaymentMethodSetting::DISCOUNT_TYPE_PERCENTAGE,
                        'discount_value' => 3.0,
                        'min_cart_amount' => 2000.0,
                        'show_notification' => true,
                        'notification_text' => 'При оплате через ЮКассу вы получите скидку {discount_percent}% ({discount} ₽). Итого к оплате: {final_amount} ₽',
                    ],
                ];
                
                $setting = PaymentMethodSetting::create(array_merge([
                    'user_id' => null,
                    'shop_id' => $shopId,
                    'payment_method_code' => $code,
                ], $defaults[$code] ?? []));
            }
            
            // Если устанавливаем как дефолтный, снимаем флаг с остальных (в рамках этого магазина)
            if (isset($validated['is_default']) && $validated['is_default']) {
                PaymentMethodSetting::whereNull('user_id')
                    ->where('shop_id', $shopId)
                    ->where('id', '!=', $setting->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
            
            // Настройки интеграции ЮКасса (привязаны к shop_id)
            if ($code === PaymentMethodSetting::CODE_YOOKASSA) {
                $setting->setYooKassaConfig($request->only([
                    'yookassa_shop_id',
                    'yookassa_secret_key',
                    'yookassa_test_shop_id',
                    'yookassa_test_secret_key',
                    'yookassa_is_test_mode',
                    'yookassa_auto_capture',
                    'yookassa_webhook_url',
                ]));
                // Не перезаписываем settings из запроса — они уже установлены в setYooKassaConfig
                unset($validated['settings']);
            }

            $setting->update($validated);
            
            DB::commit();
            
            $setting->refresh();
            $data = $setting->toArray();
            $data['name'] = $setting->getName();
            if ($setting->payment_method_code === PaymentMethodSetting::CODE_YOOKASSA) {
                $data['yookassa_integration'] = $setting->getYooKassaConfigForApi();
                // Рекомендуемый URL для webhook (по shop_id, без привязки к другим магазинам)
                $data['yookassa_integration']['yookassa_webhook_suggested_url'] = rtrim(config('app.url'), '/') . '/api/shops/' . (int) $shopId . '/webhooks/yookassa';
            }
            
            return response()->json([
                'data' => $data,
                'message' => 'Настройки способа оплаты успешно обновлены',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении настроек способа оплаты: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Ошибка при обновлении настроек',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Тест подключения к ЮКасса для магазина (интеграция привязана к shop_id).
     * POST /admin/payment-methods/yookassa/test, body: shop_id
     */
    public function testYooKassa(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $request->input('shop_id');
        if (!$shopId) {
            return response()->json(['message' => 'shop_id обязателен'], 400);
        }
        if (!$user->hasAccessToShop($shopId)) {
            return response()->json(['message' => 'Доступ к магазину запрещён'], 403);
        }
        $setting = PaymentMethodSetting::whereNull('user_id')
            ->where('shop_id', $shopId)
            ->where('payment_method_code', PaymentMethodSetting::CODE_YOOKASSA)
            ->first();
        if (!$setting) {
            return response()->json(['success' => false, 'message' => 'Настройки ЮКасса для этого магазина не найдены'], 404);
        }
        try {
            $service = new YooKassaService($setting);
            $result = $service->testConnection();
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('YooKassa test error', ['shop_id' => $shopId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Получить настройки способов оплаты для магазина (публичный для фронтенда)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSettings(Request $request): JsonResponse
    {
        $shopId = $request->input('shop_id');
        
        if (!$shopId) {
            return response()->json([
                'message' => 'shop_id обязателен',
            ], 400);
        }
        
        // Получаем настройки для магазина (любого пользователя)
        $settings = PaymentMethodSetting::getSettings(null, $shopId);
        
        // Фильтруем только активные способы оплаты
        $activeSettings = $settings->filter(function ($setting) {
            return $setting->is_enabled;
        });
        
        return response()->json([
            'data' => $activeSettings->map(function ($setting) {
                $data = $setting->toArray();
                $data['name'] = $setting->getName();
                return $data;
            })->values(),
        ]);
    }

    /**
     * Webhook от ЮКасса (публичный, привязан к shop_id).
     * POST /api/shops/{shopId}/webhooks/yookassa
     */
    public function webhookYooKassa(Request $request, $shopId): JsonResponse
    {
        Log::info('YooKassa webhook received', [
            'shop_id' => $shopId,
            'event' => $request->input('event'),
            'payment_id' => $request->input('object.id'),
        ]);

        $setting = PaymentMethodSetting::whereNull('user_id')
            ->where('shop_id', $shopId)
            ->where('payment_method_code', PaymentMethodSetting::CODE_YOOKASSA)
            ->first();

        if (!$setting || !$setting->is_enabled) {
            return response()->json(['message' => 'Integration disabled'], 403);
        }

        // TODO: разбор event (payment.succeeded, payment.canceled и т.д.) и обновление платежей/заказов
        return response()->json(['message' => 'OK'], 200);
    }
}
