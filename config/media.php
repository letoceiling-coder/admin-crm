<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки для загрузки медиа-файлов
    |
    */

    'upload' => [
        // Максимальный размер файла в килобайтах (10240 = 10 МБ)
        'max_size' => env('MEDIA_MAX_SIZE', 10240),
        
        // Разрешить все типы файлов (true) или только разрешенные (false)
        'allow_all_types' => env('MEDIA_ALLOW_ALL_TYPES', false),
        
        // Разрешенные MIME типы (если allow_all_types = false)
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'video/mp4',
            'video/avi',
            'video/quicktime',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки пагинации для списка медиа-файлов
    |
    */

    'pagination' => [
        // Количество файлов на странице по умолчанию
        'per_page_default' => env('MEDIA_PER_PAGE_DEFAULT', 20),
        
        // Максимальное количество файлов на странице
        'per_page_max' => env('MEDIA_PER_PAGE_MAX', 100),
    ],
];
