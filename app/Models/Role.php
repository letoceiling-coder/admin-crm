<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
    ];

    /**
     * Пользователи с этой ролью
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Связь через pivot таблицу shop_user
     */
    public function shopUsers()
    {
        return $this->hasMany(ShopUser::class);
    }

    /**
     * Константы уровней доступа
     */
    const LEVEL_USER = 1;
    const LEVEL_MANAGER = 2;
    const LEVEL_ADMIN = 3;
    const LEVEL_DEVELOPER = 4;
}
