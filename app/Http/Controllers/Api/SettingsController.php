<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Получить все настройки
     */
    public function index(): JsonResponse
    {
        $defaultImageId = Setting::get('default_image_id');
        $defaultImage = null;

        if ($defaultImageId) {
            $defaultImage = Media::find($defaultImageId);
        }

        return response()->json([
            'settings' => [
                'default_image_id' => $defaultImageId,
                'default_image' => $defaultImage ? [
                    'id' => $defaultImage->id,
                    'url' => $defaultImage->url,
                    'name' => $defaultImage->original_name,
                ] : null,
            ],
        ]);
    }

    /**
     * Обновить настройки
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'default_image_id' => 'nullable|integer|exists:media,id',
        ]);

        // Сохраняем настройку (может быть null для удаления)
        Setting::set('default_image_id', $request->input('default_image_id'));

        // Получаем обновленные данные
        $defaultImageId = Setting::get('default_image_id');
        $defaultImage = null;

        if ($defaultImageId) {
            $defaultImage = Media::find($defaultImageId);
        }

        return response()->json([
            'message' => 'Настройки успешно обновлены',
            'settings' => [
                'default_image_id' => $defaultImageId,
                'default_image' => $defaultImage ? [
                    'id' => $defaultImage->id,
                    'url' => $defaultImage->url,
                    'name' => $defaultImage->original_name,
                ] : null,
            ],
        ]);
    }

    /**
     * Получить фото по умолчанию (публичный метод для фронтенда)
     */
    public function getDefaultImage(): JsonResponse
    {
        $defaultImageId = Setting::get('default_image_id');
        
        if (!$defaultImageId) {
            return response()->json([
                'image' => null,
            ]);
        }

        $defaultImage = Media::find($defaultImageId);
        
        if (!$defaultImage) {
            return response()->json([
                'image' => null,
            ]);
        }

        return response()->json([
            'image' => [
                'id' => $defaultImage->id,
                'url' => $defaultImage->url,
                'name' => $defaultImage->original_name,
            ],
        ]);
    }
}
