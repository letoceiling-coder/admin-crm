<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopBotUser;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Приём обновлений от Telegram (официальный API: setWebhook, Updates).
 * https://core.telegram.org/bots/api#update
 */
class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramBotService $telegram
    ) {}

    /**
     * Обработчик webhook для магазина (бот по shop_id).
     * POST /api/telegram/webhook/{shop}
     */
    public function handle(Request $request, string $shop): JsonResponse
    {
        $shopModel = Shop::find($shop);
        if (!$shopModel || !$shopModel->telegram_bot_token) {
            return response()->json(['ok' => false], 404);
        }

        $payload = $request->all();
        if (empty($payload)) {
            return response()->json(['ok' => true]);
        }

        // Обработка /start: сохраняем/обновляем пользователя магазина + приветствие + кнопка Mini App
        $message = $payload['message'] ?? null;
        if ($message && isset($message['text']) && trim($message['text']) === '/start') {
            $this->upsertShopBotUser($shopModel, $message);
            $this->handleStartCommand($shopModel, $message);
        }

        return response()->json(['ok' => true]);
    }

    private function upsertShopBotUser(Shop $shop, array $message): void
    {
        $from = $message['from'] ?? null;
        $chat = $message['chat'] ?? null;
        if (!$from || !$chat) {
            return;
        }
        $telegramUserId = (int) ($from['id'] ?? 0);
        $chatId = (int) ($chat['id'] ?? 0);
        if ($telegramUserId === 0 || $chatId === 0) {
            return;
        }
        ShopBotUser::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'telegram_user_id' => $telegramUserId,
            ],
            [
                'telegram_chat_id' => $chatId,
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'language_code' => $from['language_code'] ?? null,
                'started_at' => now(),
            ]
        );
    }

    private function handleStartCommand(Shop $shop, array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        if ($chatId === null) {
            return;
        }

        $token = $shop->telegram_bot_token;
        $welcomeText = $shop->welcome_message ?: 'Добро пожаловать! Нажмите кнопку ниже, чтобы открыть каталог.';
        $miniAppUrl = rtrim(config('app.url', 'https://crm.neeklo.ru'), '/') . '/' . $shop->slug;
        $replyMarkup = TelegramBotService::inlineKeyboardWebApp('Открыть каталог', $miniAppUrl);

        if ($shop->welcome_photo_media_id && $shop->welcomePhoto) {
            $media = $shop->welcomePhoto;
            $photoUrl = rtrim(config('app.url', 'https://crm.neeklo.ru'), '/') . $media->url;
            $this->telegram->sendPhoto($token, $chatId, $photoUrl, $welcomeText, $replyMarkup);
        } else {
            $this->telegram->sendMessage($token, $chatId, $welcomeText, ['reply_markup' => json_encode($replyMarkup)]);
        }

        // Постоянная кнопка «Открыть приложение» для этого чата (как в express)
        $this->telegram->setChatMenuButton($token, $miniAppUrl, 'Открыть приложение', $chatId);
    }
}
