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
        Schema::table('delivery_settings', function (Blueprint $table) {
            // Тип доставки: 'fixed' - фиксированная, 'zones' - по зонам
            $table->enum('delivery_type', ['fixed', 'zones'])->default('zones')->after('is_enabled');
            // Фиксированная стоимость доставки (используется только при delivery_type = 'fixed')
            $table->decimal('fixed_delivery_cost', 10, 2)->nullable()->after('delivery_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'fixed_delivery_cost']);
        });
    }
};
