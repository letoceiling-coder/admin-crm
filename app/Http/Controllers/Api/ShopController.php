<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopStoreRequest;
use App\Http\Requests\ShopUpdateRequest;
use App\Models\Shop;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Разработчик видит все магазины
        if ($user->isDeveloper()) {
            $shops = Shop::with(['admin', 'addresses', 'phones', 'customFields'])
                ->latest()
                ->get();
        } else {
            // Администратор видит только свои магазины
            $shops = Shop::with(['admin', 'addresses', 'phones', 'customFields'])
                ->where('admin_id', $user->id)
                ->latest()
                ->get();
        }

        return response()->json($shops);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShopStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        
        // Только администраторы и разработчики могут создавать магазины
        if (!$user->isAdmin() && !$user->isDeveloper()) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        DB::beginTransaction();
        try {
            $shop = Shop::create([
                'name' => $request->name,
                'template' => $request->template ?? 'amber',
                'admin_id' => $user->id,
                'inn' => $request->inn,
                'ogrn' => $request->ogrn,
                'telegram_bot_token' => $request->telegram_bot_token,
                'telegram_bot_name' => $request->telegram_bot_name,
                'welcome_message' => $request->welcome_message,
                'welcome_photo_media_id' => $request->welcome_photo_media_id,
            ]);

            // Сохраняем адреса
            if ($request->has('addresses')) {
                foreach ($request->addresses as $address) {
                    $shop->addresses()->create(['address' => $address]);
                }
            }

            // Сохраняем телефоны
            if ($request->has('phones')) {
                foreach ($request->phones as $phone) {
                    $shop->phones()->create(['phone' => $phone]);
                }
            }

            // Сохраняем кастомные поля
            if ($request->has('custom_fields')) {
                foreach ($request->custom_fields as $field) {
                    $shop->customFields()->create([
                        'field_name' => $field['field_name'],
                        'field_value' => $field['field_value'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $shop->load(['admin', 'addresses', 'phones', 'customFields', 'welcomePhoto']);

            return response()->json($shop, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ошибка при создании магазина: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Получить магазин по slug (публичный доступ для Telegram Mini App)
     */
    public function getBySlug(string $slug): JsonResponse
    {
        $shop = Shop::where('slug', $slug)
            ->orWhere('name', $slug)
            ->first();

        if (!$shop) {
            return response()->json(['message' => 'Магазин не найден'], 404);
        }

        $shop->load(['addresses', 'phones', 'customFields']);

        return response()->json($shop);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        
        // Проверка доступа
        if (!$user->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $shop->load(['admin', 'addresses', 'phones', 'customFields', 'welcomePhoto']);

        return response()->json($shop);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShopUpdateRequest $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        
        // Проверка доступа
        if (!$user->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        DB::beginTransaction();
        try {
            $shop->update([
                'name' => $request->name,
                'template' => $request->template ?? 'amber',
                'inn' => $request->inn,
                'ogrn' => $request->ogrn,
                'telegram_bot_token' => $request->telegram_bot_token,
                'telegram_bot_name' => $request->telegram_bot_name,
                'welcome_message' => $request->welcome_message,
                'welcome_photo_media_id' => $request->welcome_photo_media_id,
            ]);

            // Автоматическая установка webhook при сохранении токена (официальный API: setWebhook)
            if ($shop->telegram_bot_token) {
                $telegramService = new TelegramBotService();
                if ($telegramService->validateToken($shop->telegram_bot_token)) {
                    $webhookUrl = rtrim(config('app.url', 'https://crm.neeklo.ru'), '/') . '/api/telegram/webhook/' . $shop->id;
                    $setResult = $telegramService->setWebhook($shop->telegram_bot_token, $webhookUrl);
                    if ($setResult['success'] && !$shop->telegram_bot_name) {
                        $me = $telegramService->getMe($shop->telegram_bot_token);
                        $bot = $me['bot'] ?? null;
                        if ($bot) {
                            $shop->update(['telegram_bot_name' => trim(($bot['first_name'] ?? '') . ' ' . ($bot['last_name'] ?? '')) . ' (@' . ($bot['username'] ?? '') . ')']);
                        }
                    }
                }
            }

            // Удаляем старые адреса и создаем новые
            $shop->addresses()->delete();
            if ($request->has('addresses')) {
                foreach ($request->addresses as $address) {
                    $shop->addresses()->create(['address' => $address]);
                }
            }

            // Удаляем старые телефоны и создаем новые
            $shop->phones()->delete();
            if ($request->has('phones')) {
                foreach ($request->phones as $phone) {
                    $shop->phones()->create(['phone' => $phone]);
                }
            }

            // Удаляем старые кастомные поля и создаем новые
            $shop->customFields()->delete();
            if ($request->has('custom_fields')) {
                foreach ($request->custom_fields as $field) {
                    $shop->customFields()->create([
                        'field_name' => $field['field_name'],
                        'field_value' => $field['field_value'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $shop->load(['admin', 'addresses', 'phones', 'customFields', 'welcomePhoto']);

            return response()->json($shop);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ошибка при обновлении магазина: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        
        // Проверка доступа
        if (!$user->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $shop->delete();

        return response()->json(['message' => 'Магазин успешно удален']);
    }

    /**
     * Проверить токен телеграм-бота (официальный API: getMe).
     * Можно передать telegram_bot_token в теле запроса для проверки до сохранения.
     */
    public function validateBotToken(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $token = $request->input('telegram_bot_token') ?: $shop->telegram_bot_token;
        if (!$token || !is_string($token)) {
            return response()->json([
                'valid' => false,
                'message' => 'Токен бота не установлен',
            ]);
        }

        $telegramService = new TelegramBotService();
        $result = $telegramService->validateToken($token);

        if ($result) {
            $botInfo = $telegramService->getMe($token);
            $bot = $botInfo['bot'] ?? null;
            $botName = $bot
                ? trim(($bot['first_name'] ?? '') . ' ' . ($bot['last_name'] ?? '')) . ' (@' . ($bot['username'] ?? '') . ')'
                : null;
            return response()->json([
                'valid' => true,
                'bot' => $bot,
                'bot_name' => $botName,
                'message' => 'Токен валиден',
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Токен невалиден',
        ]);
    }

    /**
     * Проверить токен бота без привязки к магазину (для формы создания).
     */
    public function validateBotTokenStandalone(Request $request): JsonResponse
    {
        $request->validate(['telegram_bot_token' => ['required', 'string', 'max:255']]);
        $token = $request->input('telegram_bot_token');
        $telegramService = new TelegramBotService();
        $result = $telegramService->validateToken($token);
        if ($result) {
            $botInfo = $telegramService->getMe($token);
            $bot = $botInfo['bot'] ?? null;
            $botName = $bot
                ? trim(($bot['first_name'] ?? '') . ' ' . ($bot['last_name'] ?? '')) . ' (@' . ($bot['username'] ?? '') . ')'
                : null;
            return response()->json([
                'valid' => true,
                'bot' => $bot,
                'bot_name' => $botName,
                'message' => 'Токен валиден',
            ]);
        }
        return response()->json(['valid' => false, 'message' => 'Токен невалиден']);
    }

    /**
     * Получить информацию о webhook (официальный API: getWebhookInfo).
     */
    public function getWebhookInfo(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        if (!$shop->telegram_bot_token) {
            return response()->json([
                'success' => false,
                'message' => 'Токен бота не установлен',
            ], 400);
        }

        $telegramService = new TelegramBotService();
        $result = $telegramService->getWebhookInfo($shop->telegram_bot_token);
        return response()->json($result);
    }

    /**
     * Получить информацию о боте
     */
    public function getBotInfo(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        
        // Проверка доступа
        if (!$user->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        if (!$shop->telegram_bot_token) {
            return response()->json([
                'success' => false,
                'message' => 'Токен бота не установлен',
            ]);
        }

        $telegramService = new TelegramBotService();
        $result = $telegramService->getMe($shop->telegram_bot_token);

        return response()->json($result);
    }

    /**
     * Отправить тестовое сообщение от бота
     */
    public function sendTestMessage(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        
        // Проверка доступа
        if (!$user->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $request->validate([
            'chat_id' => ['required', 'string'],
            'message' => ['nullable', 'string', 'max:4096'],
        ]);

        if (!$shop->telegram_bot_token) {
            return response()->json([
                'success' => false,
                'message' => 'Токен бота не установлен',
            ], 400);
        }

        $telegramService = new TelegramBotService();
        $message = $request->input('message', 'Тестовое сообщение от бота магазина "' . $shop->name . '"');
        
        $result = $telegramService->sendMessage(
            $shop->telegram_bot_token,
            $request->input('chat_id'),
            $message
        );

        return response()->json($result);
    }
}
