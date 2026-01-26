<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AdminApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected $adminApiService;

    public function __construct(AdminApiService $adminApiService)
    {
        $this->adminApiService = $adminApiService;
    }

    /**
     * Получить информацию о подписке
     * Сначала пытается получить актуальные данные из ADMIN, затем использует локальные настройки
     */
    public function index(): JsonResponse
    {
        $domain = config('integration.crm_domain') 
            ?: (parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
        
        $apiToken = Setting::get('api_token') ?: Setting::get('admin_api_token');

        // Пытаемся получить актуальную информацию из ADMIN
        $adminData = $this->adminApiService->getSubscriptionInfo($domain, $apiToken);

        if ($adminData['success'] && isset($adminData['data'])) {
            // Используем данные из ADMIN
            $data = $adminData['data'];
            
            // Определяем статус на основе данных из ADMIN
            // ADMIN уже проверил дату окончания и установил правильный статус
            $status = $data['status'] ?? 'pending';
            
            // Если статус 'expired', то is_active должен быть false
            if ($status === 'expired') {
                $data['is_active'] = false;
            }
            
            // Если is_active = true и статус не 'expired', то статус = 'active'
            if (isset($data['is_active']) && $data['is_active'] === true && $status !== 'expired') {
                $status = 'active';
            }
            
            // Логируем данные для отладки
            Log::info('SubscriptionController: возвращаем данные из ADMIN', [
                'has_login' => isset($data['login']),
                'login' => $data['login'] ?? null,
                'has_plan' => isset($data['plan']),
                'plan' => $data['plan'] ?? null,
            ]);
            
            return response()->json([
                'subscription' => [
                    'status' => $status,
                    'api_token' => $data['api_token'] ?? null,
                    'expires_at' => $data['expires_at'] ?? $data['subscription_end'] ?? null,
                    'subscription_start' => $data['subscription_start'] ?? null,
                    'subscription_end' => $data['subscription_end'] ?? null,
                    'domain' => $data['domain'] ?? $domain,
                    'login' => $data['login'] ?? null,
                    'is_active' => $data['is_active'] ?? false,
                    'plan' => $data['plan'] ?? null,
                    'application_id' => $data['application_id'] ?? null,
                ],
            ]);
        }

        // Если не удалось получить из ADMIN, используем локальные настройки
        Log::warning('SubscriptionController: не удалось получить данные из ADMIN, используются локальные настройки', [
            'error' => $adminData['error'] ?? 'unknown',
            'response_status' => $adminData['response_status'] ?? null,
        ]);

        $apiToken = Setting::get('api_token') ?: Setting::get('admin_api_token');
        $expiresAt = Setting::get('expires_at') ?: Setting::get('admin_api_token_expires_at');
        $subscriptionStatus = Setting::get('subscription_status', 'pending');
        
        // Определяем is_active на основе токена и даты окончания
        $isActive = !empty($apiToken) && (!empty($expiresAt) ? strtotime($expiresAt) > time() : true);
        
        // Если is_active = true, статус должен быть 'active'
        $status = $isActive ? 'active' : $subscriptionStatus;

        return response()->json([
            'subscription' => [
                'status' => $status,
                'api_token' => $apiToken ? (strlen($apiToken) > 13 ? substr($apiToken, 0, 10) . '...' : $apiToken) : null,
                'expires_at' => $expiresAt,
                'domain' => $domain,
                'is_active' => $isActive,
            ],
        ]);
    }
}
