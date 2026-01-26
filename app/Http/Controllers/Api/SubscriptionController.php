<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AdminApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected AdminApiService $adminApi;

    public function __construct(AdminApiService $adminApi)
    {
        $this->adminApi = $adminApi;
    }

    /**
     * Получить информацию о подписке
     */
    public function index(): JsonResponse
    {
        $apiToken = Setting::get('api_token');
        $expiresAt = Setting::get('expires_at');
        $subscriptionStatus = Setting::get('subscription_status', 'pending');
        $domain = config('integration.crm_domain') 
            ?: (parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');

        $subscriptionData = [
            'status' => $subscriptionStatus,
            'api_token' => $apiToken ? substr($apiToken, 0, 10) . '...' : null,
            'expires_at' => $expiresAt,
            'subscription_start' => Setting::get('subscription_start'),
            'domain' => $domain,
            'login' => null,
            'plan' => null,
            'is_active' => !empty($apiToken) && (!empty($expiresAt) ? strtotime($expiresAt) > time() : true),
        ];

        // Пытаемся получить дополнительную информацию из ADMIN API
        // Используем admin_api_token, если он есть, иначе api_token
        $adminApiToken = Setting::get('admin_api_token') ?: $apiToken;
        if ($adminApiToken && $domain) {
            try {
                $subscriberInfo = $this->getSubscriberFromAdmin($domain, $adminApiToken);
                if ($subscriberInfo) {
                    $subscriptionData = array_merge($subscriptionData, [
                        'login' => $subscriberInfo['login'] ?? null,
                        'plan' => $subscriberInfo['plan'] ?? ($subscriberInfo['plan'] ? ['name' => $subscriberInfo['plan']] : null),
                        'subscription_start' => $subscriberInfo['subscription_start'] ?? $subscriptionData['subscription_start'],
                        'subscription_end' => $subscriberInfo['subscription_end'] ?? $expiresAt,
                        'is_active' => $subscriberInfo['is_active'] ?? $subscriptionData['is_active'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Не удалось получить информацию о подписке из ADMIN API', [
                    'error' => $e->getMessage(),
                    'domain' => $domain,
                ]);
            }
        }

        return response()->json([
            'subscription' => $subscriptionData,
        ]);
    }

    /**
     * Получить информацию о подписчике из ADMIN API
     */
    protected function getSubscriberFromAdmin(string $domain, string $apiToken): ?array
    {
        $baseUrl = config('integration.admin_api_url') ?: rtrim((string) env('APP_CRM_URL', ''), '/');
        if (empty($baseUrl)) {
            return null;
        }

        try {
            // Используем правильный путь к ADMIN API
            $url = rtrim($baseUrl, '/') . '/admin/subscribers';
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Accept' => 'application/json',
                ])
                ->get($url, [
                    'domain' => $domain,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // Ищем подписчика с нужным доменом
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $subscriber) {
                        if (isset($subscriber['domain']) && $subscriber['domain'] === $domain) {
                            return $subscriber;
                        }
                    }
                }
                // Если вернулся один элемент (не массив)
                if (isset($data['data']['domain']) && $data['data']['domain'] === $domain) {
                    return $data['data'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при запросе информации о подписчике из ADMIN', [
                'error' => $e->getMessage(),
                'domain' => $domain,
            ]);
        }

        return null;
    }
}
