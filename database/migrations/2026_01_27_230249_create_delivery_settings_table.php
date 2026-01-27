<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Пользователь, создавший настройки
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('shop_id')->nullable(); // Магазин, к которому привязаны настройки
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->string('yandex_geocoder_api_key')->nullable(); // API ключ Яндекс.Геокодера
            $table->text('origin_address')->nullable(); // Адрес начальной точки доставки (текстовый)
            $table->decimal('origin_latitude', 10, 8)->nullable(); // Широта начальной точки
            $table->decimal('origin_longitude', 11, 8)->nullable(); // Долгота начальной точки
            $table->string('default_city')->default('Екатеринбург'); // Город по умолчанию для поиска адресов
            $table->decimal('free_delivery_threshold', 10, 2)->nullable(); // Порог бесплатной доставки
            $table->json('delivery_zones')->nullable(); // Зоны доставки: [{"max_distance": 3, "cost": 300}, ...]
            $table->boolean('is_enabled')->default(false); // Включена ли система расчета доставки
            $table->decimal('min_delivery_order_total_rub', 10, 2)->default(3000); // Минимальный заказ для доставки
            $table->integer('delivery_min_lead_hours')->default(3); // Минимальное время подготовки (часы)
            $table->timestamps();
            
            // Уникальная комбинация user_id и shop_id (одна запись на комбинацию)
            $table->unique(['user_id', 'shop_id']);
            $table->index('user_id');
            $table->index('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_settings');
    }
};
