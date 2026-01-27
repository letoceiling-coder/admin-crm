<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sku',
        'price',
        'weight',
        'protein',
        'fat',
        'carbs',
        'calories',
        'category_id',
        'unit_id',
        'image_id',
        'stock',
        'position',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'integer',
        'protein' => 'decimal:2',
        'fat' => 'decimal:2',
        'carbs' => 'decimal:2',
        'calories' => 'integer',
        'stock' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Категория товара
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Единица измерения
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Главное изображение товара
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    /**
     * Все изображения товара
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_media')
            ->withPivot('position')
            ->orderBy('product_media.position')
            ->withTimestamps();
    }
}
