<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopBotUser extends Model
{
    protected $table = 'shop_bot_users';

    protected $fillable = [
        'shop_id',
        'telegram_user_id',
        'telegram_chat_id',
        'username',
        'first_name',
        'last_name',
        'language_code',
        'started_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->last_name,
            $this->username ? '@' . $this->username : null,
        ]);
        return implode(' ', $parts) ?: (string) $this->telegram_user_id;
    }
}
