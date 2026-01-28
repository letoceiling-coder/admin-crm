<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Shop;
use App\Models\ShopBotUser;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Пользователи бота магазина (Telegram) и рассылка.
 */
class ShopBotUserController extends Controller
{
    public function __construct(
        private TelegramBotService $telegram
    ) {}

    /**
     * Список пользователей бота по магазину.
     * GET /admin/shops/{shop}/bot-users
     */
    public function index(Request $request, Shop $shop): JsonResponse
    {
        if (!$request->user()->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $users = ShopBotUser::where('shop_id', $shop->id)
            ->orderBy('started_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * Рассылка: отправить сообщение (текст / фото / видео) всем или выбранным пользователям.
     * POST /admin/shops/{shop}/bot-users/broadcast
     * Body: { type: 'text'|'photo'|'video', text?: string, media_id?: number, recipient_ids?: number[] }
     * recipient_ids пустой или отсутствует = всем.
     */
    public function broadcast(Request $request, Shop $shop): JsonResponse
    {
        if (!$request->user()->hasAccessToShop($shop->id)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:text,photo,video',
            'text' => 'nullable|string|max:4096',
            'media_id' => 'nullable|integer|exists:media,id',
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => 'integer|exists:shop_bot_users,id',
        ]);

        $token = $shop->telegram_bot_token;
        if (!$token) {
            return response()->json(['message' => 'У магазина не настроен токен бота'], 422);
        }

        $query = ShopBotUser::where('shop_id', $shop->id);
        if (!empty($validated['recipient_ids'])) {
            $query->whereIn('id', $validated['recipient_ids']);
        }
        $recipients = $query->get();
        if ($recipients->isEmpty()) {
            return response()->json(['message' => 'Нет получателей для рассылки'], 422);
        }

        $type = $validated['type'];
        $text = $validated['text'] ?? '';
        $mediaId = $validated['media_id'] ?? null;

        $mediaUrl = null;
        if ($mediaId && $type !== 'text') {
            $media = Media::find($mediaId);
            if ($media) {
                $mediaUrl = rtrim(config('app.url', 'https://crm.neeklo.ru'), '/') . $media->url;
            }
        }

        $sent = 0;
        $failed = [];

        foreach ($recipients as $user) {
            $result = $this->sendToRecipient($token, $type, $user->telegram_chat_id, $text, $mediaUrl);
            if ($result['success']) {
                $sent++;
            } else {
                $failed[] = ['id' => $user->id, 'error' => $result['error'] ?? 'Unknown'];
            }
        }

        Log::info('Shop broadcast', [
            'shop_id' => $shop->id,
            'type' => $type,
            'sent' => $sent,
            'failed_count' => count($failed),
        ]);

        return response()->json([
            'message' => 'Рассылка выполнена',
            'sent' => $sent,
            'total' => $recipients->count(),
            'failed' => $failed,
        ]);
    }

    private function sendToRecipient(
        string $token,
        string $type,
        int|string $chatId,
        string $text,
        ?string $mediaUrl
    ): array {
        if ($type === 'text') {
            return $this->telegram->sendMessage($token, $chatId, $text ?: '—', []);
        }
        if ($type === 'photo' && $mediaUrl) {
            return $this->telegram->sendPhoto($token, $chatId, $mediaUrl, $text ?: null, []);
        }
        if ($type === 'video' && $mediaUrl) {
            return $this->telegram->sendVideo($token, $chatId, $mediaUrl, $text ?: null);
        }
        if ($type === 'photo' || $type === 'video') {
            return $this->telegram->sendMessage($token, $chatId, $text ?: '—', []);
        }
        return ['success' => false, 'error' => 'Invalid type or media'];
    }
}
