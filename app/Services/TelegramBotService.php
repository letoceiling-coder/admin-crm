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
