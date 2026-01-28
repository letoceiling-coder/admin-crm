<?php

namespace App\Services\Payment;

use App\Models\PaymentMethodSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис ЮКасса для CRM. Настройки привязаны к shop_id (магазин CRM) через PaymentMethodSetting.
 */
class YooKassaService
{
    protected PaymentMethodSetting $setting;
    protected array $config;
    protected string $baseUrl = 'https://api.yookassa.ru/v3';

    public function __construct(PaymentMethodSetting $setting)
    {
        $this->setting = $setting;
        if ($setting->payment_method_code !== PaymentMethodSetting::CODE_YOOKASSA) {
            throw new \InvalidArgumentException('PaymentMethodSetting must be yookassa');
        }
        $this->config = $setting->getYooKassaConfig();
    }

    protected function getShopId(): ?string
    {
        return $this->config['shop_id'] ?? null;
    }

    protected function getSecretKey(): ?string
    {
        return $this->config['secret_key'] ?? null;
    }

    protected function getHeaders(?string $idempotenceKey = null): array
    {
        $shopId = $this->getShopId();
        $secretKey = $this->getSecretKey();
        if (!$shopId || !$secretKey) {
            throw new \Exception('Настройки ЮКасса не заполнены для этого магазина');
        }
        $auth = base64_encode("{$shopId}:{$secretKey}");
        return [
            'Authorization' => "Basic {$auth}",
            'Content-Type' => 'application/json',
            'Idempotence-Key' => $idempotenceKey ?? $this->generateIdempotenceKey(),
        ];
    }

    protected function generateIdempotenceKey(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Создать платёж
     */
    public function createPayment(array $data, ?string $idempotenceKey = null): array
    {
        $payload = [
            'amount' => [
                'value' => number_format($data['amount'], 2, '.', ''),
                'currency' => $data['currency'] ?? 'RUB',
            ],
            'confirmation' => [
                'type' => $data['confirmation_type'] ?? 'redirect',
                'return_url' => $data['return_url'] ?? url('/'),
            ],
            'description' => $data['description'] ?? 'Оплата заказа',
            'metadata' => $data['metadata'] ?? [],
        ];
        if (isset($data['capture'])) {
            $payload['capture'] = $data['capture'];
        }
        if (isset($data['receipt'])) {
            $payload['receipt'] = $data['receipt'];
        }

        $response = Http::withHeaders($this->getHeaders($idempotenceKey))
            ->post("{$this->baseUrl}/payments", $payload);

        if (!$response->successful()) {
            Log::error('YooKassa createPayment error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Ошибка создания платежа: ' . $response->body());
        }
        return $response->json();
    }

    /**
     * Получить информацию о платеже
     */
    public function getPayment(string $paymentId): array
    {
        $response = Http::withHeaders($this->getHeaders())->get("{$this->baseUrl}/payments/{$paymentId}");
        if (!$response->successful()) {
            throw new \Exception('Ошибка получения платежа: ' . $response->body());
        }
        return $response->json();
    }

    /**
     * Подтвердить платёж (capture)
     */
    public function capturePayment(string $paymentId, ?array $amount = null): array
    {
        $data = [];
        if ($amount) {
            $data['amount'] = [
                'value' => number_format($amount['value'], 2, '.', ''),
                'currency' => $amount['currency'] ?? 'RUB',
            ];
        }
        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/payments/{$paymentId}/capture", $data);
        if (!$response->successful()) {
            throw new \Exception('Ошибка подтверждения платежа: ' . $response->body());
        }
        return $response->json();
    }

    /**
     * Отменить платёж
     */
    public function cancelPayment(string $paymentId): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/payments/{$paymentId}/cancel");
        if (!$response->successful()) {
            throw new \Exception('Ошибка отмены платежа: ' . $response->body());
        }
        return $response->json();
    }

    /**
     * Создать возврат (полный или частичный)
     */
    public function createRefund(string $paymentId, array $data): array
    {
        $refundPayload = [
            'payment_id' => $paymentId,
            'amount' => [
                'value' => number_format($data['amount'], 2, '.', ''),
                'currency' => $data['currency'] ?? 'RUB',
            ],
        ];
        if (isset($data['description'])) {
            $refundPayload['description'] = $data['description'];
        }
        if (isset($data['receipt'])) {
            $refundPayload['receipt'] = $data['receipt'];
        }

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/refunds", $refundPayload);

        if (!$response->successful()) {
            Log::error('YooKassa createRefund error', ['payment_id' => $paymentId, 'body' => $response->body()]);
            throw new \Exception('Ошибка создания возврата: ' . $response->body());
        }
        return $response->json();
    }

    /**
     * Проверка подключения к API ЮКасса
     */
    public function testConnection(): array
    {
        try {
            $shopId = $this->getShopId();
            $secretKey = $this->getSecretKey();
            if (!$shopId || !$secretKey) {
                return [
                    'success' => false,
                    'message' => 'Заполните Shop ID и Secret Key (тестовые или рабочие в зависимости от режима)',
                ];
            }
            $response = Http::withHeaders($this->getHeaders())->timeout(10)
                ->get("{$this->baseUrl}/payments", ['limit' => 1]);

            if ($response->successful()) {
                $mode = ($this->config['is_test_mode'] ?? true) ? 'тестовый' : 'рабочий';
                return [
                    'success' => true,
                    'message' => "Подключение к ЮКасса успешно ({$mode} режим)",
                ];
            }
            $body = $response->json();
            $message = $body['description'] ?? $body['message'] ?? $response->body();
            if ($response->status() === 401) {
                $message = 'Неверный Shop ID или Secret Key';
            } elseif ($response->status() === 403) {
                $message = 'Доступ запрещён. Проверьте права ключа';
            }
            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('YooKassa testConnection error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Ошибка подключения: ' . $e->getMessage()];
        }
    }
}
