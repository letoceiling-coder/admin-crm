<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'field_name',
        'field_value',
    ];

    /**
     * Магазин, к которому принадлежит кастомное поле
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
