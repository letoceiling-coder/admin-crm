<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель настроек способов оплаты
 * 
 * @property int $id
 * @property int|null $user_id
 * @property int|null $shop_id
 * @property string $payment_method_code
 * @property bool $is_enabled
 * @property bool $is_default
 * @property bool $available_for_delivery
 * @property bool $available_for_pickup
 * @property int $sort_order
 * @property string $discount_type
 * @property float|null $discount_value
 * @property float|null $min_cart_amount
 * @property bool $show_notification
 * @property string|null $notification_text
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PaymentMethodSetting extends Model
{
    use HasFactory;

    /**
     * Имя таблицы
     * 
     * @var string
     */
    protected $table = 'payment_method_settings';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'shop_id',
        'payment_method_code',
        'is_enabled',
        'is_default',
        'available_for_delivery',
        'available_for_pickup',
        'sort_order',
        'discount_type',
        'discount_value',
        'min_cart_amount',
        'show_notification',
        'notification_text',
        'settings',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'available_for_delivery' => 'boolean',
        'available_for_pickup' => 'boolean',
        'sort_order' => 'integer',
        'discount_value' => 'decimal:2',
        'min_cart_amount' => 'decimal:2',
        'show_notification' => 'boolean',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Типы скидок
     */
    const DISCOUNT_TYPE_NONE = 'none';
    const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    const DISCOUNT_TYPE_FIXED = 'fixed';

    /**
     * Коды способов оплаты
     */
    const CODE_CASH = 'cash';
    const CODE_YOOKASSA = 'yookassa';

    /**
     * Пользователь, создавший настройки
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Магазин, к которому привязаны настройки
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Получить настройки способов оплаты для пользователя и магазина
     * 
     * @param int|null $userId
     * @param int|null $shopId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSettings(?int $userId = null, ?int $shopId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::query();
        
        if ($userId !== null) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id');
        }
        
        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        } else {
            $query->whereNull('shop_id');
        }
        
        $settings = $query->orderBy('sort_order')->get();
        
        // Если нет настроек, создаем по умолчанию
        $defaultMethods = [
            self::CODE_CASH => [
                'is_enabled' => true,
                'available_for_delivery' => true,
                'available_for_pickup' => true,
                'sort_order' => 2,
                'discount_type' => self::DISCOUNT_TYPE_NONE,
                'is_default' => true, // Наличные по умолчанию
            ],
            self::CODE_YOOKASSA => [
                'is_enabled' => true,
                'available_for_delivery' => true,
                'available_for_pickup' => false,
                'sort_order' => 1,
                'discount_type' => self::DISCOUNT_TYPE_PERCENTAGE,
                'discount_value' => 3.0,
                'min_cart_amount' => 2000.0,
                'show_notification' => true,
                'notification_text' => 'При оплате через ЮКассу вы получите скидку {discount_percent}% ({discount} ₽). Итого к оплате: {final_amount} ₽',
                'is_default' => false,
            ],
        ];
        
        $result = collect();
        $hasDefault = false;
        
        foreach ($defaultMethods as $code => $defaults) {
            $setting = $settings->firstWhere('payment_method_code', $code);
            if (!$setting) {
                try {
                    // Если уже есть способ оплаты по умолчанию, не устанавливаем is_default для текущего
                    if (isset($defaults['is_default']) && $defaults['is_default'] && $hasDefault) {
                        $defaults['is_default'] = false;
                    }
                    
                    $setting = static::create(array_merge([
                        'user_id' => $userId,
                        'shop_id' => $shopId,
                        'payment_method_code' => $code,
                    ], $defaults));
                    
                    if ($setting->is_default) {
                        $hasDefault = true;
                    }
                } catch (\Exception $e) {
                    // Если возникла ошибка (например, дубликат), пытаемся найти существующую запись
                    $setting = static::where(function ($query) use ($userId) {
                        if ($userId !== null) {
                            $query->where('user_id', $userId);
                        } else {
                            $query->whereNull('user_id');
                        }
                    })
                        ->where(function ($query) use ($shopId) {
                            if ($shopId !== null) {
                                $query->where('shop_id', $shopId);
                            } else {
                                $query->whereNull('shop_id');
                            }
                        })
                        ->where('payment_method_code', $code)
                        ->first();
                    
                    if (!$setting) {
                        // Если все еще не найдено, пропускаем этот способ оплаты
                        Log::error('Error creating payment method setting', [
                            'code' => $code,
                            'user_id' => $userId,
                            'shop_id' => $shopId,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                }
            } else {
                if ($setting->is_default) {
                    $hasDefault = true;
                }
            }
            $result->push($setting);
        }
        
        // Убеждаемся, что только один способ оплаты помечен как default
        $defaultCount = $result->where('is_default', true)->count();
        if ($defaultCount > 1) {
            // Оставляем только первый как default
            $firstDefault = $result->where('is_default', true)->first();
            $result->each(function ($item) use ($firstDefault) {
                if ($item->id !== $firstDefault->id && $item->is_default) {
                    $item->is_default = false;
                    $item->save();
                }
            });
        } elseif ($defaultCount === 0 && $result->count() > 0) {
            // Если нет default, устанавливаем первый
            $first = $result->first();
            $first->is_default = true;
            $first->save();
        }
        
        return $result->sortBy('sort_order')->values();
    }

    /**
     * Рассчитать скидку для суммы корзины
     * 
     * @param float $cartAmount
     * @return array ['discount' => float, 'final_amount' => float, 'applied' => bool]
     */
    public function calculateDiscount(float $cartAmount): array
    {
        $discount = 0;
        $applied = false;

        if ($this->discount_type === self::DISCOUNT_TYPE_NONE) {
            return [
                'discount' => 0,
                'final_amount' => $cartAmount,
                'applied' => false,
            ];
        }

        // Проверяем минимальную сумму корзины
        if ($this->min_cart_amount && $cartAmount < $this->min_cart_amount) {
            return [
                'discount' => 0,
                'final_amount' => $cartAmount,
                'applied' => false,
            ];
        }

        // Рассчитываем скидку
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE && $this->discount_value) {
            $discount = ($cartAmount * $this->discount_value) / 100;
            $applied = true;
        } elseif ($this->discount_type === self::DISCOUNT_TYPE_FIXED && $this->discount_value) {
            $discount = min($this->discount_value, $cartAmount); // Не больше суммы корзины
            $applied = true;
        }

        $finalAmount = max(0, $cartAmount - $discount);

        return [
            'discount' => round($discount, 2),
            'final_amount' => round($finalAmount, 2),
            'applied' => $applied,
        ];
    }

    /**
     * Получить текст уведомления (если нужно показывать)
     */
    public function getNotificationMessage(float $cartAmount): ?string
    {
        if (!$this->show_notification || !$this->notification_text) {
            return null;
        }

        $discountInfo = $this->calculateDiscount($cartAmount);
        
        if (!$discountInfo['applied']) {
            return null;
        }

        // Заменяем плейсхолдеры в тексте уведомления
        $message = $this->notification_text;
        $message = str_replace('{discount}', number_format($discountInfo['discount'], 2, '.', ' '), $message);
        $message = str_replace('{final_amount}', number_format($discountInfo['final_amount'], 2, '.', ' '), $message);
        $message = str_replace('{cart_amount}', number_format($cartAmount, 2, '.', ' '), $message);
        
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE && $this->discount_value) {
            $message = str_replace('{discount_percent}', number_format($this->discount_value, 0), $message);
        }

        return $message;
    }

    /**
     * Получить название способа оплаты
     */
    public function getName(): string
    {
        $names = [
            self::CODE_CASH => 'Наличные',
            self::CODE_YOOKASSA => 'ЮКасса',
        ];
        
        return $names[$this->payment_method_code] ?? $this->payment_method_code;
    }

    /**
     * Получить описание способа оплаты
     */
    public function getDescription(): ?string
    {
        $descriptions = [
            self::CODE_CASH => 'Оплата наличными при получении',
            self::CODE_YOOKASSA => 'Оплата картой через ЮКассу',
        ];
        
        return $descriptions[$this->payment_method_code] ?? null;
    }
}
