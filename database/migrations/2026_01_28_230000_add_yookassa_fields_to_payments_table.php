<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_provider', 50)->nullable()->after('payment_method'); // yookassa, etc.
            $table->string('transaction_id')->nullable()->after('payment_provider'); // id платежа провайдера (например YooKassa payment_id)
            $table->json('provider_payload')->nullable()->after('transaction_id'); // сырой payload/ответы провайдера

            $table->index(['shop_id', 'payment_provider', 'transaction_id'], 'payments_shop_provider_tx_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_shop_provider_tx_idx');
            $table->dropColumn(['payment_provider', 'transaction_id', 'provider_payload']);
        });
    }
};

