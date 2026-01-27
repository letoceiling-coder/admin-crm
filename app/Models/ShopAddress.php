<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'address',
    ];

    /**
     * Магазин, к которому принадлежит адрес
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
