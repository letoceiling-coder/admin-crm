<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\v1\FolderController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\DeliverySettingsController;
use App\Http\Controllers\Api\PaymentMethodSettingsController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\ShopBotUserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Публичные роуты авторизации (доступны всем)
Route::prefix('auth')->group(function () {
    Route::get('/csrf-cookie', [AuthController::class, 'csrfCookie']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Webhook для Telegram-ботов (публичный, без auth — Telegram шлёт POST сюда)
Route::post('/telegram/webhook/{shop}', [TelegramWebhookController::class, 'handle']);

// Публичные роуты для Telegram Mini App (доступны всем)
Route::get('/shops/slug/{slug}', [ShopController::class, 'getBySlug']);
Route::get('/shops/{shopId}/categories', [CategoryController::class, 'getByShop']);
Route::get('/shops/{shopId}/products', [ProductController::class, 'getByShop']);
Route::get('/products/{product}', [ProductController::class, 'show'])->where('product', '[0-9]+');
Route::get('/settings/default-image', [SettingsController::class, 'getDefaultImage']);

// Публичные роуты для настроек доставки (для фронтенда)
Route::prefix('v1')->group(function () {
    Route::get('delivery-settings', [DeliverySettingsController::class, 'getSettings']);
    Route::post('delivery/calculate-cost', [DeliverySettingsController::class, 'calculateCost']);
    Route::post('delivery/address-suggestions', [DeliverySettingsController::class, 'getAddressSuggestions']);
    
    // Публичные роуты для способов оплаты (для фронтенда)
    Route::get('payment-methods', [PaymentMethodSettingsController::class, 'getSettings']);
});

// Деплой (защищен токеном)
Route::post('/deploy', [DeployController::class, 'deploy'])
    ->middleware('deploy.token');

// Защищенные роуты (только для авторизованных)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Административные роуты (только для администраторов и менеджеров)
    Route::middleware('admin.access')->prefix('admin')->group(function () {
        // Информация о подписке
        Route::get('/subscription', [SubscriptionController::class, 'index']);
        
        // Управление магазинами
        Route::post('validate-bot-token', [ShopController::class, 'validateBotTokenStandalone']);
        Route::apiResource('shops', ShopController::class);
        Route::prefix('shops/{shop}')->group(function () {
            Route::post('/validate-bot-token', [ShopController::class, 'validateBotToken']);
            Route::get('/bot-info', [ShopController::class, 'getBotInfo']);
            Route::get('/webhook-info', [ShopController::class, 'getWebhookInfo']);
            Route::post('/send-test-message', [ShopController::class, 'sendTestMessage']);
            Route::get('/bot-users', [ShopBotUserController::class, 'index']);
            Route::post('/bot-users/broadcast', [ShopBotUserController::class, 'broadcast']);
        });

        // Управление каталогом
        // Категории
        Route::post('categories/update-positions', [CategoryController::class, 'updatePositions']);
        Route::apiResource('categories', CategoryController::class);
        
        // Товары
        Route::post('products/update-positions', [ProductController::class, 'updatePositions']);
        Route::apiResource('products', ProductController::class);
        
        // Единицы измерения
        Route::post('units/update-positions', [UnitController::class, 'updatePositions']);
        Route::apiResource('units', UnitController::class);

        // Управление заказами, доставками и платежами
        Route::apiResource('orders', OrderController::class);
        Route::apiResource('deliveries', DeliveryController::class);
        Route::apiResource('payments', PaymentController::class);

        // Управление настройками
        Route::get('settings', [SettingsController::class, 'index']);
        Route::put('settings', [SettingsController::class, 'update']);
        
        // Настройки доставки
        Route::get('settings/delivery', [DeliverySettingsController::class, 'index']);
        Route::put('settings/delivery', [DeliverySettingsController::class, 'update']);
        
        // Настройки способов оплаты
        Route::get('payment-methods', [PaymentMethodSettingsController::class, 'index']);
        Route::put('payment-methods/{code}', [PaymentMethodSettingsController::class, 'update']);
    });

    // Media API (v1)
    Route::prefix('v1')->group(function () {
        // Folders
        Route::get('folders/tree/all', [FolderController::class, 'tree'])->name('folders.tree');
        Route::post('folders/update-positions', [FolderController::class, 'updatePositions'])->name('folders.update-positions');
        Route::post('folders/{id}/restore', [FolderController::class, 'restore'])->name('folders.restore');
        Route::apiResource('folders', FolderController::class);
        
        // Media
        Route::post('media/{id}/restore', [MediaController::class, 'restore'])->name('media.restore');
        Route::delete('media/trash/empty', [MediaController::class, 'emptyTrash'])->name('media.trash.empty');
        Route::apiResource('media', MediaController::class);
    });
});
