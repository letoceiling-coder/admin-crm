<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->boolean('protected')->default(false)->after('position');
            $table->boolean('is_trash')->default(false)->after('protected');
        });
        
        // Устанавливаем защиту для системных папок (id 1-4)
        // Системные папки должны быть с user_id = NULL (доступны всем) и protected = true (нельзя удалить)
        DB::table('folders')->whereIn('id', [1, 2, 3, 4])->update([
            'user_id' => null, // Системные папки доступны всем пользователям
            'protected' => true, // Нельзя удалить
            'is_trash' => DB::raw('CASE WHEN id = 4 THEN 1 ELSE 0 END')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropColumn(['protected', 'is_trash']);
        });
    }
};
