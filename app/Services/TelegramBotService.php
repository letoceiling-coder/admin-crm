<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private string $baseUrl = 'https://api.telegram.org/bot';

    /**
     * Отправить сообщение в Telegram
     *
     * @param string $token Токен бота
     * @param int|string $chatId ID чата или username
     * @param string $text Текст сообщения
     * @param array $options Дополнительные опции (parse_mode, reply_markup и т.д.)
     * @return array{success: bool, message_id?: int, error?: string}
     */
    public function sendMessage(
        string $token,
        int|string $chatId,
        string $text,
        array $options = []
    ): array {
        $url = $this->baseUrl . $token . '/sendMessage';

        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $options);

        try {
            $response = Http::timeout(10)
                ->asJson()
                ->post($url, $payload);

            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning('TelegramBotService: ошибка отправки сообщения', [
                    'chat_id' => $chatId,
                    'error' => $error,
                    'status' => $response->status(),
                ]);

                return ['success' => false, 'error' => $error];
            }

            $data = $response->json('result');
            $messageId = $data['message_id'] ?? null;

            return [
                'success' => true,
                'message_id' => $messageId,
            ];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: исключение при отправке сообщения', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получить информацию о боте
     *
     * @param string $token Токен бота
     * @return array{success: bool, bot?: array, error?: string}
     */
    public function getMe(string $token): array
    {
        $url = $this->baseUrl . $token . '/getMe';

        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning('TelegramBotService: ошибка получения информации о боте', [
                    'error' => $error,
                    'status' => $response->status(),
                ]);

                return ['success' => false, 'error' => $error];
            }

            $bot = $response->json('result');

            return [
                'success' => true,
                'bot' => $bot,
            ];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: исключение при получении информации о боте', [
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Проверить валидность токена бота
     *
     * @param string $token Токен бота
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        $result = $this->getMe($token);
        return $result['success'] ?? false;
    }

    /**
     * Установить описание бота (полное, «О чём этот бот»). Показывается на странице бота до /start.
     * https://core.telegram.org/bots/api#setmydescription
     *
     * @param string $token Токен бота
     * @param string $description Текст 0-512 символов
     * @param string|null $languageCode Двухбуквенный код языка (ru, en и т.д.)
     * @return array{success: bool, error?: string}
     */
    public function setMyDescription(string $token, string $description, ?string $languageCode = null): array
    {
        $url = $this->baseUrl . $token . '/setMyDescription';
        $payload = ['description' => $description];
        if ($languageCode !== null && $languageCode !== '') {
            $payload['language_code'] = $languageCode;
        }
        return $this->postBotInfo($url, $payload, 'setMyDescription');
    }

    /**
     * Установить краткое описание бота. Показывается на странице бота до /start.
     * https://core.telegram.org/bots/api#setmyshortdescription
     *
     * @param string $token Токен бота
     * @param string $shortDescription Текст 0-120 символов
     * @param string|null $languageCode Двухбуквенный код языка
     * @return array{success: bool, error?: string}
     */
    public function setMyShortDescription(string $token, string $shortDescription, ?string $languageCode = null): array
    {
        $url = $this->baseUrl . $token . '/setMyShortDescription';
        $payload = ['short_description' => $shortDescription];
        if ($languageCode !== null && $languageCode !== '') {
            $payload['language_code'] = $languageCode;
        }
        return $this->postBotInfo($url, $payload, 'setMyShortDescription');
    }

    /**
     * Получить текущее описание бота (getMyDescription).
     *
     * @param string $token Токен бота
     * @param string|null $languageCode Код языка
     * @return array{success: bool, description?: string, error?: string}
     */
    public function getMyDescription(string $token, ?string $languageCode = null): array
    {
        $url = $this->baseUrl . $token . '/getMyDescription';
        $url .= $languageCode ? '?language_code=' . urlencode($languageCode) : '';
        try {
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                return ['success' => false, 'error' => $error];
            }
            $result = $response->json('result', []);
            return ['success' => true, 'description' => $result['description'] ?? ''];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: getMyDescription', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получить краткое описание бота (getMyShortDescription).
     *
     * @param string $token Токен бота
     * @param string|null $languageCode Код языка
     * @return array{success: bool, short_description?: string, error?: string}
     */
    public function getMyShortDescription(string $token, ?string $languageCode = null): array
    {
        $url = $this->baseUrl . $token . '/getMyShortDescription';
        $url .= $languageCode ? '?language_code=' . urlencode($languageCode) : '';
        try {
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                return ['success' => false, 'error' => $error];
            }
            $result = $response->json('result', []);
            return ['success' => true, 'short_description' => $result['short_description'] ?? ''];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: getMyShortDescription', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function postBotInfo(string $url, array $payload, string $methodName): array
    {
        try {
            $response = Http::timeout(10)->asJson()->post($url, $payload);
            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning("TelegramBotService: {$methodName}", ['error' => $error]);
                return ['success' => false, 'error' => $error];
            }
            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error("TelegramBotService: {$methodName}", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Установить webhook для бота
     *
     * @param string $token Токен бота
     * @param string $url URL для webhook
     * @param array $options Дополнительные опции
     * @return array{success: bool, error?: string}
     */
    public function setWebhook(string $token, string $url, array $options = []): array
    {
        $apiUrl = $this->baseUrl . $token . '/setWebhook';

        $payload = array_merge([
            'url' => $url,
        ], $options);

        try {
            $response = Http::timeout(10)
                ->asJson()
                ->post($apiUrl, $payload);

            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning('TelegramBotService: ошибка установки webhook', [
                    'url' => $url,
                    'error' => $error,
                    'status' => $response->status(),
                ]);

                return ['success' => false, 'error' => $error];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: исключение при установке webhook', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Удалить webhook для бота
     *
     * @param string $token Токен бота
     * @return array{success: bool, error?: string}
     */
    public function deleteWebhook(string $token): array
    {
        $url = $this->baseUrl . $token . '/deleteWebhook';

        try {
            $response = Http::timeout(10)->post($url);

            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning('TelegramBotService: ошибка удаления webhook', [
                    'error' => $error,
                    'status' => $response->status(),
                ]);

                return ['success' => false, 'error' => $error];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: исключение при удалении webhook', [
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получить информацию о webhook
     *
     * @param string $token Токен бота
     * @return array{success: bool, webhook?: array, error?: string}
     */
    public function getWebhookInfo(string $token): array
    {
        $url = $this->baseUrl . $token . '/getWebhookInfo';

        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning('TelegramBotService: ошибка получения информации о webhook', [
                    'error' => $error,
                    'status' => $response->status(),
                ]);

                return ['success' => false, 'error' => $error];
            }

            $webhook = $response->json('result');

            return [
                'success' => true,
                'webhook' => $webhook,
            ];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: исключение при получении информации о webhook', [
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Отправить фото в Telegram (официальный API: sendPhoto)
     * https://core.telegram.org/bots/api#sendphoto
     *
     * @param string $token Токен бота
     * @param int|string $chatId ID чата
     * @param string $photo URL фото или file_id
     * @param string|null $caption Подпись к фото
     * @param array $replyMarkup reply_markup (InlineKeyboardMarkup и т.д.)
     * @return array{success: bool, message_id?: int, error?: string}
     */
    public function sendPhoto(
        string $token,
        int|string $chatId,
        string $photo,
        ?string $caption = null,
        array $replyMarkup = []
    ): array {
        $url = $this->baseUrl . $token . '/sendPhoto';

        $payload = [
            'chat_id' => $chatId,
            'photo' => $photo,
        ];
        if ($caption !== null && $caption !== '') {
            $payload['caption'] = $caption;
        }
        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = Http::timeout(15)->asJson()->post($url, $payload);

            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning('TelegramBotService: ошибка отправки фото', [
                    'chat_id' => $chatId,
                    'error' => $error,
                ]);
                return ['success' => false, 'error' => $error];
            }

            $data = $response->json('result');
            return ['success' => true, 'message_id' => $data['message_id'] ?? null];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: исключение при отправке фото', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Отправить видео в Telegram (официальный API: sendVideo)
     * https://core.telegram.org/bots/api#sendvideo
     *
     * @param string $token Токен бота
     * @param int|string $chatId ID чата
     * @param string $video URL видео или file_id
     * @param string|null $caption Подпись к видео
     * @return array{success: bool, message_id?: int, error?: string}
     */
    public function sendVideo(
        string $token,
        int|string $chatId,
        string $video,
        ?string $caption = null
    ): array {
        $url = $this->baseUrl . $token . '/sendVideo';

        $payload = [
            'chat_id' => $chatId,
            'video' => $video,
        ];
        if ($caption !== null && $caption !== '') {
            $payload['caption'] = $caption;
        }

        try {
            $response = Http::timeout(30)->asJson()->post($url, $payload);

            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning('TelegramBotService: ошибка отправки видео', [
                    'chat_id' => $chatId,
                    'error' => $error,
                ]);
                return ['success' => false, 'error' => $error];
            }

            $data = $response->json('result');
            return ['success' => true, 'message_id' => $data['message_id'] ?? null];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: исключение при отправке видео', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Собрать InlineKeyboardMarkup с одной кнопкой Web App (официальный API)
     * https://core.telegram.org/bots/api#inlinekeyboardmarkup
     *
     * @param string $text Текст кнопки
     * @param string $webAppUrl URL Mini App (HTTPS)
     * @return array{inline_keyboard: array}
     */
    public static function inlineKeyboardWebApp(string $text, string $webAppUrl): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => $text, 'web_app' => ['url' => $webAppUrl]],
                ],
            ],
        ];
    }

    /**
     * Отправить документ в Telegram
     *
     * @param string $token Токен бота
     * @param int|string $chatId ID чата или username
     * @param string|\Illuminate\Http\UploadedFile $document Путь к файлу или UploadedFile
     * @param string|null $caption Подпись к документу
     * @return array{success: bool, message_id?: int, error?: string}
     */
    public function sendDocument(
        string $token,
        int|string $chatId,
        string|\Illuminate\Http\UploadedFile $document,
        ?string $caption = null
    ): array {
        $url = $this->baseUrl . $token . '/sendDocument';

        try {
            $payload = [
                'chat_id' => $chatId,
            ];

            if ($caption) {
                $payload['caption'] = $caption;
            }

            // Если это путь к файлу
            if (is_string($document) && file_exists($document)) {
                $payload['document'] = fopen($document, 'r');
            } elseif ($document instanceof \Illuminate\Http\UploadedFile) {
                $payload['document'] = $document;
            } else {
                return ['success' => false, 'error' => 'Invalid document'];
            }

            $response = Http::timeout(30)
                ->attach('document', $payload['document'])
                ->post($url, array_filter($payload, fn($key) => $key !== 'document', ARRAY_FILTER_USE_KEY));

            if (!$response->successful()) {
                $error = $response->json('description', 'Unknown error');
                Log::warning('TelegramBotService: ошибка отправки документа', [
                    'chat_id' => $chatId,
                    'error' => $error,
                    'status' => $response->status(),
                ]);

                return ['success' => false, 'error' => $error];
            }

            $data = $response->json('result');
            $messageId = $data['message_id'] ?? null;

            return [
                'success' => true,
                'message_id' => $messageId,
            ];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService: исключение при отправке документа', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
