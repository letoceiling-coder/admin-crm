<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminApiService
{
    /**
     * Отправить заявку на подписку в ADMIN после регистрации в CRM.
     * При успехе сохраняет api_token и expires_at в settings.
     *
     * @return array{success: bool, token?: string, expires_at?: string, error?: string}
     */
    public function sendSubscriptionApplication(string $domain, string $name, string $email): array
    {
        $baseUrl = config('integration.admin_api_url') ?: rtrim((string) env('APP_CRM_URL', ''), '/');
        if (empty($baseUrl)) {
            Log::warning('AdminApiService: APP_CRM_URL не задан, пропуск отправки заявки. Задайте APP_CRM_URL в .env (например http://admin.loc/api/v1).');

            return ['success' => false, 'error' => 'APP_CRM_URL not configured'];
        }

        $url = rtrim($baseUrl, '/') . '/subscription-applications';
        $payload = [
            'domain' => $domain,
            'name' => $name,
            'email' => $email,
        ];

        Log::info('AdminApiService: отправка заявки в ADMIN', [
            'url' => $url,
            'domain' => $domain,
            'email' => $email,
        ]);

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            if (!$response->successful()) {
                $body = $response->json();
                $msg = is_array($body) ? ($body['message'] ?? ($body['errors'] ?? $response->body())) : $response->body();
                Log::warning('AdminApiService: ADMIN вернул ошибку', [
                    'status' => $response->status(),
                    'body' => $body,
                    'domain' => $domain,
                    'email' => $email,
                ]);

                return ['success' => false, 'error' => is_string($msg) ? $msg : json_encode($msg)];
            }

            $data = $response->json('data');
            $token = $data['api_token'] ?? null;
            $expiresAt = $data['expires_at'] ?? null;

            if ($token) {
                Setting::set('admin_api_token', $token);
                if ($expiresAt) {
                    Setting::set('admin_api_token_expires_at', $expiresAt);
                }
                Log::info('AdminApiService: заявка создана, токен сохранён', [
                    'domain' => $domain,
                    'application_id' => $data['id'] ?? null,
                ]);
            }

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $expiresAt,
            ];
        } catch (ConnectionException $e) {
            Log::warning('AdminApiService: не удалось подключиться к ADMIN', [
                'url' => $url,
                'message' => $e->getMessage(),
                'domain' => $domain,
                'email' => $email,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('AdminApiService: ошибка при отправке заявки', [
                'url' => $url,
                'message' => $e->getMessage(),
                'domain' => $domain,
                'email' => $email,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
