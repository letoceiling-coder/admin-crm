<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
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

        return response()->json([
            'subscription' => [
                'status' => $subscriptionStatus,
                'api_token' => $apiToken ? substr($apiToken, 0, 10) . '...' : null,
                'expires_at' => $expiresAt,
                'domain' => $domain,
                'is_active' => !empty($apiToken) && (!empty($expiresAt) ? strtotime($expiresAt) > time() : true),
            ],
        ]);
    }
}
