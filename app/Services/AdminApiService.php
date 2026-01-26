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
                // Сохраняем токен в нескольких ключах для совместимости
                Setting::set('api_token', $token);
                Setting::set('admin_api_token', $token);
                if ($expiresAt) {
                    Setting::set('expires_at', $expiresAt);
                    Setting::set('admin_api_token_expires_at', $expiresAt);
                }
                Setting::set('subscription_status', 'pending');
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

    /**
     * Получить информацию о подписке из ADMIN
     * 
     * @param string|null $domain Домен CRM
     * @param string|null $apiToken API токен подписки
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getSubscriptionInfo(?string $domain = null, ?string $apiToken = null): array
    {
        $baseUrl = config('integration.admin_api_url') ?: rtrim((string) env('APP_CRM_URL', ''), '/');
        if (empty($baseUrl)) {
            Log::warning('AdminApiService: APP_CRM_URL не задан, невозможно получить информацию о подписке');
            return ['success' => false, 'error' => 'APP_CRM_URL not configured'];
        }

        // Если не указаны параметры, пытаемся получить из настроек
        if (!$domain && !$apiToken) {
            $domain = config('integration.crm_domain') 
                ?: (parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
            // Получаем полный токен (если он сохранен полностью, а не частично скрыт)
            $savedToken = Setting::get('api_token') ?: Setting::get('admin_api_token');
            // Используем токен только если он полный (не содержит ...)
            if ($savedToken && strpos($savedToken, '...') === false && strlen($savedToken) > 20) {
                $apiToken = $savedToken;
            }
        }

        // URL должен быть /api/v1/subscription
        $url = rtrim($baseUrl, '/') . '/v1/subscription';
        $params = [];
        if ($domain) {
            $params['domain'] = $domain;
        }
        if ($apiToken) {
            $params['api_token'] = $apiToken;
        }

        Log::info('AdminApiService: запрос информации о подписке', [
            'url' => $url,
            'domain' => $domain,
            'has_token' => !empty($apiToken),
        ]);

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get($url, $params);

            if (!$response->successful()) {
                $body = $response->json();
                $msg = is_array($body) ? ($body['message'] ?? $response->body()) : $response->body();
                Log::warning('AdminApiService: ADMIN вернул ошибку при запросе подписки', [
                    'status' => $response->status(),
                    'body' => $body,
                    'url' => $url,
                    'params' => $params,
                ]);

                return [
                    'success' => false, 
                    'error' => is_string($msg) ? $msg : json_encode($msg),
                    'response_status' => $response->status(),
                ];
            }

            $responseData = $response->json();
            
            // Проверяем структуру ответа
            if (!isset($responseData['data'])) {
                Log::error('AdminApiService: ответ ADMIN не содержит ключ "data"', [
                    'response_keys' => array_keys($responseData),
                    'full_response' => $responseData,
                ]);
                return ['success' => false, 'error' => 'Invalid response structure from ADMIN'];
            }
            
            $data = $responseData['data'];
            
            // Логируем полученные данные для отладки
            Log::info('AdminApiService: получены данные о подписке', [
                'data_keys' => array_keys($data),
                'has_login' => isset($data['login']),
                'login' => $data['login'] ?? null,
                'has_plan' => isset($data['plan']),
                'plan' => $data['plan'] ?? null,
                'plan_name' => $data['plan']['name'] ?? null,
            ]);
            
            // Обновляем локальные настройки, если получены новые данные
            if ($data && isset($data['api_token'])) {
                $token = $data['api_token'];
                // Сохраняем полный токен только если он не скрыт
                if (strpos($token, '...') === false && strlen($token) > 20) {
                    Setting::set('api_token', $token);
                    Setting::set('admin_api_token', $token);
                }
            }
            
            if ($data && isset($data['expires_at'])) {
                Setting::set('expires_at', $data['expires_at']);
            }
            
            if ($data && isset($data['subscription_end'])) {
                Setting::set('expires_at', $data['subscription_end']);
            }
            
            // Определяем и сохраняем статус: если is_active = true, то статус = 'active'
            if ($data && isset($data['is_active']) && $data['is_active'] === true) {
                Setting::set('subscription_status', 'active');
            } elseif ($data && isset($data['status'])) {
                Setting::set('subscription_status', $data['status']);
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (ConnectionException $e) {
            Log::warning('AdminApiService: не удалось подключиться к ADMIN', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('AdminApiService: ошибка при получении информации о подписке', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
