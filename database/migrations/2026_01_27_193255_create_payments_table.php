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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Администратор, создавший платеж
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('order_id')->nullable(); // Связь с заказом (опционально)
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->string('payment_number')->unique(); // Номер платежа
            $table->string('payer_name'); // Имя плательщика
            $table->string('payer_email')->nullable(); // Email плательщика
            $table->string('payer_phone')->nullable(); // Телефон плательщика
            $table->decimal('amount', 10, 2); // Сумма платежа
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'online', 'other'])->default('cash'); // Способ оплаты
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending'); // Статус платежа
            $table->text('notes')->nullable(); // Примечания
            $table->timestamp('payment_date')->nullable(); // Дата платежа
            $table->timestamp('paid_at')->nullable(); // Дата фактической оплаты
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('order_id');
            $table->index('payment_number');
            $table->index('status');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
