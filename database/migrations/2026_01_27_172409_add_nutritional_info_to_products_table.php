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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('weight')->nullable()->after('price')->comment('Вес порции в граммах');
            $table->decimal('protein', 8, 2)->nullable()->after('weight')->comment('Белки в граммах');
            $table->decimal('fat', 8, 2)->nullable()->after('protein')->comment('Жиры в граммах');
            $table->decimal('carbs', 8, 2)->nullable()->after('fat')->comment('Углеводы в граммах');
            $table->integer('calories')->nullable()->after('carbs')->comment('Калорийность в ккал');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight', 'protein', 'fat', 'carbs', 'calories']);
        });
    }
};
