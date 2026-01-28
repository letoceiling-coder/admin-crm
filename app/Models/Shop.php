<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'template',
        'admin_id',
        'inn',
        'ogrn',
        'telegram_bot_token',
        'telegram_bot_name',
        'telegram_bot_short_description',
        'telegram_bot_description',
        'welcome_message',
        'welcome_photo_media_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shop) {
            if (empty($shop->slug)) {
                $shop->slug = static::generateUniqueSlug($shop->name);
            }
        });

        static::updating(function ($shop) {
            if ($shop->isDirty('name') && empty($shop->slug)) {
                $shop->slug = static::generateUniqueSlug($shop->name, $shop->id);
            }
        });
    }

    /**
     * Генерация уникального slug на основе названия
     */
    protected static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

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

    /**
     * Адреса магазина
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(ShopAddress::class);
    }

    /**
     * Телефоны магазина
     */
    public function phones(): HasMany
    {
        return $this->hasMany(ShopPhone::class);
    }

    /**
     * Кастомные поля магазина
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(ShopCustomField::class);
    }

    /**
     * Категории магазина
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Товары магазина
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Заказы магазина
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Доставки магазина
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Платежи магазина
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Медиа для приветственного фото бота
     */
    public function welcomePhoto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Media::class, 'welcome_photo_media_id');
    }

    /**
     * Пользователи бота магазина (Telegram, при /start)
     */
    public function botUsers(): HasMany
    {
        return $this->hasMany(ShopBotUser::class);
    }
}
