<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\ShopBotUser;
use Illuminate\Support\Facades\Log;

/**
 * Валидация initData от Telegram Web App и получение пользователя бота.
 * https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 */
class TelegramInitDataService
{
    /**
     * Валидирует initData и возвращает данные пользователя.
     * secret_key = HMAC_SHA256("WebAppData", bot_token)
     * calculated_hash = HMAC_SHA256(data_check_string, secret_key)
     *
     * @param string $initData Строка initData из Telegram.WebApp.initData
     * @param string $botToken Токен бота магазина
     * @return array{valid: bool, user?: array{id: int, first_name?: string, last_name?: string, username?: string}, error?: string}
     */
    public function validate(string $initData, string $botToken): array
    {
        if (empty(trim($initData))) {
            return ['valid' => false, 'error' => 'initData пустой'];
        }

        $params = [];
        parse_str($initData, $params);
        $hash = $params['hash'] ?? null;
        unset($params['hash']);

        if (!$hash) {
            return ['valid' => false, 'error' => 'hash отсутствует'];
        }

        ksort($params);
        $dataCheckString = implode("\n", array_map(fn ($k, $v) => "{$k}={$v}", array_keys($params), $params));

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $hash)) {
            Log::warning('TelegramInitDataService: неверный hash', ['init_data_length' => strlen($initData)]);
            return ['valid' => false, 'error' => 'Неверная подпись initData'];
        }

        $userJson = $params['user'] ?? null;
        if (!$userJson) {
            return ['valid' => false, 'error' => 'user отсутствует в initData'];
        }

        $user = json_decode($userJson, true);
        if (!is_array($user) || empty($user['id'])) {
            return ['valid' => false, 'error' => 'Некорректный user в initData'];
        }

        return [
            'valid' => true,
            'user' => [
                'id' => (int) $user['id'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'username' => $user['username'] ?? null,
            ],
        ];
    }

    /**
     * Находит или создаёт ShopBotUser по shop_id и telegram_user_id из initData.
     */
    public function getOrCreateShopBotUser(Shop $shop, array $telegramUser): ?ShopBotUser
    {
        $telegramUserId = (int) ($telegramUser['id'] ?? 0);
        if ($telegramUserId === 0) {
            return null;
        }

        $botUser = ShopBotUser::firstOrCreate(
            [
                'shop_id' => $shop->id,
                'telegram_user_id' => $telegramUserId,
            ],
            [
                'telegram_chat_id' => $telegramUserId,
                'username' => $telegramUser['username'] ?? null,
                'first_name' => $telegramUser['first_name'] ?? null,
                'last_name' => $telegramUser['last_name'] ?? null,
                'started_at' => now(),
            ]
        );

        $botUser->update([
            'first_name' => $telegramUser['first_name'] ?? $botUser->first_name,
            'last_name' => $telegramUser['last_name'] ?? $botUser->last_name,
            'username' => $telegramUser['username'] ?? $botUser->username,
        ]);

        return $botUser;
    }
}
