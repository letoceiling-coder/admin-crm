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
// Обработка запросов к /miniapp/assets/* и других файлов из miniapp
Route::get('/miniapp/{any}', function ($any) {
    // Защита от path traversal
    $any = str_replace('..', '', $any);
    $any = ltrim($any, '/');
    
    $filePath = public_path("miniapp/{$any}");
    $basePath = public_path('miniapp');
    
    // Проверяем, что файл находится внутри базовой директории
    $realFilePath = realpath($filePath);
    $realBasePath = realpath($basePath);
    
    if (!$realFilePath || !$realBasePath || !str_starts_with($realFilePath, $realBasePath)) {
        abort(404, "File not found: {$any}");
    }
    
    if (!file_exists($realFilePath) || !is_file($realFilePath)) {
        abort(404, "File not found: {$any}");
    }
    
    // Определяем MIME тип по расширению
    $extension = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'ico' => 'image/x-icon',
        'html' => 'text/html; charset=utf-8',
    ];
    
    $mimeType = $mimeTypes[$extension] ?? mime_content_type($realFilePath);
    
    return response()->file($realFilePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('any', '.+');

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
