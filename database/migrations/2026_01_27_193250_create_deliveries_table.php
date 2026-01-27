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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Администратор, создавший доставку
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('order_id')->nullable(); // Связь с заказом (опционально)
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->string('delivery_number')->unique(); // Номер доставки
            $table->string('recipient_name'); // Имя получателя
            $table->string('recipient_phone'); // Телефон получателя
            $table->text('delivery_address'); // Адрес доставки
            $table->text('notes')->nullable(); // Примечания
            $table->enum('status', ['pending', 'in_transit', 'delivered', 'cancelled'])->default('pending'); // Статус доставки
            $table->timestamp('delivery_date')->nullable(); // Дата доставки
            $table->timestamp('delivered_at')->nullable(); // Дата фактической доставки
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('order_id');
            $table->index('delivery_number');
            $table->index('status');
            $table->index('delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
