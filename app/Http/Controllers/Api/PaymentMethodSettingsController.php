<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodSettingsRequest;
use App\Models\PaymentMethodSetting;
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
        
        $settings = PaymentMethodSetting::getSettings($user->id, $shopId);
        
        return response()->json([
            'data' => $settings->map(function ($setting) {
                $data = $setting->toArray();
                // Добавляем название способа оплаты
                $data['name'] = $setting->getName();
                return $data;
            }),
        ]);
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
            
            // Получаем или создаем настройки
            $setting = PaymentMethodSetting::where('user_id', $user->id)
                ->where('shop_id', $shopId)
                ->where('payment_method_code', $code)
                ->first();
            
            if (!$setting) {
                // Создаем настройки по умолчанию
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
                    'user_id' => $user->id,
                    'shop_id' => $shopId,
                    'payment_method_code' => $code,
                ], $defaults[$code] ?? []));
            }
            
            // Если устанавливаем как дефолтный, снимаем флаг с остальных
            if (isset($validated['is_default']) && $validated['is_default']) {
                PaymentMethodSetting::where('user_id', $user->id)
                    ->where('shop_id', $shopId)
                    ->where('id', '!=', $setting->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
            
            // Обновляем настройки
            $setting->update($validated);
            
            DB::commit();
            
            $setting->refresh();
            $data = $setting->toArray();
            $data['name'] = $setting->getName();
            
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
}
