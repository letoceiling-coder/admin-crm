<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function cacheKey(string $key): string
    {
        return 'setting.' . $key;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::cacheKey($key);
        $value = Cache::rememberForever($cacheKey, function () use ($key) {
            $row = static::where('key', $key)->first();

            return $row?->value;
        });

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $value = is_string($value) ? $value : json_encode($value);
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::cacheKey($key));
    }
}
