<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SubscriptionController;

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
        Route::apiResource('shops', ShopController::class);
        Route::prefix('shops/{shop}')->group(function () {
            Route::post('/validate-bot-token', [ShopController::class, 'validateBotToken']);
            Route::get('/bot-info', [ShopController::class, 'getBotInfo']);
            Route::post('/send-test-message', [ShopController::class, 'sendTestMessage']);
        });
    });
});
