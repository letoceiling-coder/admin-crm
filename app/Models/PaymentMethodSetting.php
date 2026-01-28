<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     * @return \Illuminate\Support\Collection
     */
    public static function getSettings(?int $userId = null, ?int $shopId = null): \Illuminate\Support\Collection
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
        
        // Используем транзакцию для атомарности
        try {
            DB::beginTransaction();
            
            foreach ($defaultMethods as $code => $defaults) {
                $setting = $settings->firstWhere('payment_method_code', $code);
                if (!$setting) {
                    // Если уже есть способ оплаты по умолчанию, не устанавливаем is_default для текущего
                    if (isset($defaults['is_default']) && $defaults['is_default'] && $hasDefault) {
                        $defaults['is_default'] = false;
                    }
                    
                    try {
                        $setting = static::create(array_merge([
                            'user_id' => $userId,
                            'shop_id' => $shopId,
                            'payment_method_code' => $code,
                        ], $defaults));
                        
                        if ($setting->is_default) {
                            $hasDefault = true;
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Если возникла ошибка уникального индекса, пытаемся найти существующую запись
                        if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
                            $setting = static::where(function ($q) use ($userId) {
                                if ($userId !== null) {
                                    $q->where('user_id', $userId);
                                } else {
                                    $q->whereNull('user_id');
                                }
                            })
                                ->where(function ($q) use ($shopId) {
                                    if ($shopId !== null) {
                                        $q->where('shop_id', $shopId);
                                    } else {
                                        $q->whereNull('shop_id');
                                    }
                                })
                                ->where('payment_method_code', $code)
                                ->first();
                            
                            if (!$setting) {
                                Log::error('Error creating payment method setting - duplicate but not found', [
                                    'code' => $code,
                                    'user_id' => $userId,
                                    'shop_id' => $shopId,
                                    'error' => $e->getMessage(),
                                ]);
                                continue;
                            }
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    if ($setting->is_default) {
                        $hasDefault = true;
                    }
                }
                
                if ($setting) {
                    $result->push($setting);
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in getSettings for payment methods', [
                'user_id' => $userId,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
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

    /**
     * Ключи настроек ЮКасса в settings
     */
    public const YOOKASSA_SHOP_ID = 'yookassa_shop_id';
    public const YOOKASSA_SECRET_KEY = 'yookassa_secret_key_encrypted';
    public const YOOKASSA_TEST_SHOP_ID = 'yookassa_test_shop_id';
    public const YOOKASSA_TEST_SECRET_KEY = 'yookassa_test_secret_key_encrypted';
    public const YOOKASSA_IS_TEST_MODE = 'yookassa_is_test_mode';
    public const YOOKASSA_AUTO_CAPTURE = 'yookassa_auto_capture';
    public const YOOKASSA_WEBHOOK_URL = 'yookassa_webhook_url';

    /**
     * Получить настройки интеграции ЮКасса (только для payment_method_code = yookassa).
     * Секретные ключи расшифровываются. Не отдавать в API — только для сервиса.
     */
    public function getYooKassaConfig(): array
    {
        if ($this->payment_method_code !== self::CODE_YOOKASSA) {
            return [];
        }
        $s = $this->settings ?? [];
        $isTest = (bool) ($s[self::YOOKASSA_IS_TEST_MODE] ?? true);
        $shopId = $isTest
            ? ($s[self::YOOKASSA_TEST_SHOP_ID] ?? null)
            : ($s[self::YOOKASSA_SHOP_ID] ?? null);
        $secretKey = $isTest
            ? $this->decryptYooKassaSecret($s[self::YOOKASSA_TEST_SECRET_KEY] ?? null)
            : $this->decryptYooKassaSecret($s[self::YOOKASSA_SECRET_KEY] ?? null);
        return [
            'shop_id' => $shopId,
            'secret_key' => $secretKey,
            'is_test_mode' => $isTest,
            'auto_capture' => (bool) ($s[self::YOOKASSA_AUTO_CAPTURE] ?? true),
            'webhook_url' => $s[self::YOOKASSA_WEBHOOK_URL] ?? null,
        ];
    }

    /**
     * Получить настройки ЮКасса для отображения в админке (без секретных ключей).
     */
    public function getYooKassaConfigForApi(): array
    {
        if ($this->payment_method_code !== self::CODE_YOOKASSA) {
            return [];
        }
        $s = $this->settings ?? [];
        return [
            'yookassa_shop_id' => $s[self::YOOKASSA_SHOP_ID] ?? '',
            'yookassa_test_shop_id' => $s[self::YOOKASSA_TEST_SHOP_ID] ?? '',
            'yookassa_is_test_mode' => (bool) ($s[self::YOOKASSA_IS_TEST_MODE] ?? true),
            'yookassa_auto_capture' => (bool) ($s[self::YOOKASSA_AUTO_CAPTURE] ?? true),
            'yookassa_webhook_url' => $s[self::YOOKASSA_WEBHOOK_URL] ?? '',
            'yookassa_has_secret_key' => !empty($s[self::YOOKASSA_SECRET_KEY]),
            'yookassa_has_test_secret_key' => !empty($s[self::YOOKASSA_TEST_SECRET_KEY]),
        ];
    }

    /**
     * Сохранить настройки интеграции ЮКасса. Секретные ключи шифруются.
     */
    public function setYooKassaConfig(array $input): void
    {
        if ($this->payment_method_code !== self::CODE_YOOKASSA) {
            return;
        }
        $s = $this->settings ?? [];
        if (isset($input['yookassa_shop_id'])) {
            $s[self::YOOKASSA_SHOP_ID] = trim((string) $input['yookassa_shop_id']) ?: null;
        }
        if (isset($input['yookassa_test_shop_id'])) {
            $s[self::YOOKASSA_TEST_SHOP_ID] = trim((string) $input['yookassa_test_shop_id']) ?: null;
        }
        if (array_key_exists('yookassa_is_test_mode', $input)) {
            $s[self::YOOKASSA_IS_TEST_MODE] = (bool) $input['yookassa_is_test_mode'];
        }
        if (array_key_exists('yookassa_auto_capture', $input)) {
            $s[self::YOOKASSA_AUTO_CAPTURE] = (bool) $input['yookassa_auto_capture'];
        }
        if (isset($input['yookassa_webhook_url'])) {
            $s[self::YOOKASSA_WEBHOOK_URL] = trim((string) $input['yookassa_webhook_url']) ?: null;
        }
        $secret = isset($input['yookassa_secret_key']) ? trim((string) $input['yookassa_secret_key']) : null;
        if ($secret !== null && $secret !== '') {
            $s[self::YOOKASSA_SECRET_KEY] = Crypt::encryptString($secret);
        }
        $testSecret = isset($input['yookassa_test_secret_key']) ? trim((string) $input['yookassa_test_secret_key']) : null;
        if ($testSecret !== null && $testSecret !== '') {
            $s[self::YOOKASSA_TEST_SECRET_KEY] = Crypt::encryptString($testSecret);
        }
        $this->settings = $s;
    }

    private function decryptYooKassaSecret(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') {
            return null;
        }
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            Log::warning('PaymentMethodSetting: failed to decrypt yookassa secret', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
