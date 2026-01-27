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
        Schema::create('payment_method_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Пользователь, создавший настройки
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('shop_id')->nullable(); // Магазин, к которому привязаны настройки
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->string('payment_method_code', 50); // Код способа оплаты ('cash', 'yookassa')
            $table->boolean('is_enabled')->default(true); // Включен ли способ оплаты
            $table->boolean('is_default')->default(false); // Способ оплаты по умолчанию
            $table->boolean('available_for_delivery')->default(true); // Доступен при доставке
            $table->boolean('available_for_pickup')->default(true); // Доступен при самовывозе
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->enum('discount_type', ['none', 'percentage', 'fixed'])->default('none'); // Тип скидки
            $table->decimal('discount_value', 10, 2)->nullable(); // Значение скидки
            $table->decimal('min_cart_amount', 10, 2)->nullable(); // Минимальная сумма корзины
            $table->boolean('show_notification')->default(false); // Показывать уведомление
            $table->text('notification_text')->nullable(); // Текст уведомления
            $table->json('settings')->nullable(); // Дополнительные настройки (для ЮКассы)
            $table->timestamps();
            
            // Уникальная комбинация user_id, shop_id и payment_method_code
            $table->unique(['user_id', 'shop_id', 'payment_method_code'], 'pms_user_shop_code_unique');
            $table->index('user_id');
            $table->index('shop_id');
            $table->index('payment_method_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_settings');
    }
};
