<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'payment_number',
        'payer_name',
        'payer_email',
        'payer_phone',
        'amount',
        'payment_method',
        'status',
        'notes',
        'payment_date',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Администратор, создавший платеж
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Заказ, к которому относится платеж
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
