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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Администратор, создавший заказ
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('order_number')->unique(); // Номер заказа
            $table->string('customer_name'); // Имя клиента
            $table->string('customer_email')->nullable(); // Email клиента
            $table->string('customer_phone')->nullable(); // Телефон клиента
            $table->text('customer_address')->nullable(); // Адрес клиента
            $table->text('notes')->nullable(); // Примечания
            $table->decimal('total_amount', 10, 2)->default(0); // Общая сумма
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending'); // Статус заказа
            $table->timestamp('order_date')->nullable(); // Дата заказа
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('order_number');
            $table->index('status');
            $table->index('order_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
