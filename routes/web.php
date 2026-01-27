<?php

use Illuminate\Support\Facades\Route;
use App\Models\Shop;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Роуты для статических файлов Mini App (должны быть ДО общего роута)
Route::get('/miniapp/{any}', function ($any) {
    $path = public_path("miniapp/{$any}");
    if (file_exists($path) && is_file($path)) {
        $mimeType = mime_content_type($path);
        return response()->file($path, ['Content-Type' => $mimeType]);
    }
    abort(404);
})->where('any', '.*');

// Роут для Telegram Mini App по slug магазина
Route::get('/{shopSlug}', function ($shopSlug) {
    // Проверяем существование магазина
    $shop = Shop::where('slug', $shopSlug)
        ->orWhere('name', $shopSlug)
        ->first();
    
    if (!$shop) {
        // Если магазин не найден, отдаем админ-панель
        return view('app');
    }
    
    // Проверяем наличие собранных файлов miniapp
    $miniappIndex = public_path('miniapp/index.html');
    if (!file_exists($miniappIndex)) {
        abort(404, 'Mini App не собран. Выполните: php artisan deploy');
    }
    
    // Отдаем index.html для SPA
    return response()->file($miniappIndex);
})->where('shopSlug', '[a-zA-Z0-9\-_]+');

// Роуты для вложенных путей Mini App (product, cart, checkout и т.д.)
Route::get('/{shopSlug}/{path}', function ($shopSlug, $path) {
    // Проверяем существование магазина
    $shop = Shop::where('slug', $shopSlug)
        ->orWhere('name', $shopSlug)
        ->first();
    
    if (!$shop) {
        // Если магазин не найден, отдаем админ-панель
        return view('app');
    }
    
    // Проверяем наличие собранных файлов miniapp
    $miniappIndex = public_path('miniapp/index.html');
    if (!file_exists($miniappIndex)) {
        abort(404, 'Mini App не собран. Выполните: php artisan deploy');
    }
    
    // Отдаем index.html для SPA (React Router обработает маршрут)
    return response()->file($miniappIndex);
})->where('shopSlug', '[a-zA-Z0-9\-_]+')
  ->where('path', '.*');

// Общий роут для админ-панели (должен быть последним)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
