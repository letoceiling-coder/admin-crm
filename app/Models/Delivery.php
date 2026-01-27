<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'shop_id',
        'order_id',
        'delivery_number',
        'recipient_name',
        'recipient_phone',
        'delivery_address',
        'notes',
        'status',
        'delivery_date',
        'delivered_at',
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Администратор, создавший доставку
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Заказ, к которому относится доставка
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Магазин доставки
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
