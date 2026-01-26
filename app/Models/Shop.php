<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'admin_id',
    ];

    /**
     * Администратор, создавший магазин
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Пользователи, связанные с магазином через pivot
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * Менеджеры магазина
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_user')
            ->wherePivot('role_id', Role::LEVEL_MANAGER)
            ->withTimestamps();
    }
}
