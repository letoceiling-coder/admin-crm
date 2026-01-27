<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Роль пользователя
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Магазины, созданные пользователем (как администратор)
     */
    public function ownedShops(): HasMany
    {
        return $this->hasMany(Shop::class, 'admin_id');
    }

    /**
     * Магазины, в которых пользователь является менеджером
     */
    public function managedShops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_user')
            ->wherePivot('role_id', Role::LEVEL_MANAGER)
            ->withTimestamps();
    }

    /**
     * Все магазины пользователя (как администратор или менеджер)
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        if (!$this->role_id) {
            return false;
        }
        
        // Если роль не загружена, загружаем её
        if (!$this->relationLoaded('role') && !$this->role) {
            $this->load('role');
        }
        
        return $this->role && $this->role->level === Role::LEVEL_ADMIN;
    }

    /**
     * Проверка, является ли пользователь менеджером
     */
    public function isManager(): bool
    {
        if (!$this->role_id) {
            return false;
        }
        
        // Если роль не загружена, загружаем её
        if (!$this->relationLoaded('role') && !$this->role) {
            $this->load('role');
        }
        
        return $this->role && $this->role->level === Role::LEVEL_MANAGER;
    }

    /**
     * Проверка, является ли пользователь разработчиком
     */
    public function isDeveloper(): bool
    {
        if (!$this->role_id) {
            return false;
        }
        
        // Если роль не загружена, загружаем её
        if (!$this->relationLoaded('role') && !$this->role) {
            $this->load('role');
        }
        
        return $this->role && $this->role->level === Role::LEVEL_DEVELOPER;
    }

    /**
     * Проверка доступа к магазину
     */
    public function hasAccessToShop(int $shopId): bool
    {
        // Разработчик имеет доступ ко всем магазинам
        if ($this->isDeveloper()) {
            return true;
        }

        // Администратор имеет доступ к своим магазинам
        if ($this->isAdmin()) {
            return $this->ownedShops()->where('id', $shopId)->exists();
        }

        // Менеджер имеет доступ к назначенным магазинам
        if ($this->isManager()) {
            return $this->shops()->where('shops.id', $shopId)->exists();
        }

        return false;
    }
}
