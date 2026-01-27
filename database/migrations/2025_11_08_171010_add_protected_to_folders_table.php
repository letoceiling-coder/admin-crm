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
            if (!Schema::hasColumn('folders', 'protected')) {
                $table->boolean('protected')->default(false)->after('position');
            }
            if (!Schema::hasColumn('folders', 'is_trash')) {
                $table->boolean('is_trash')->default(false)->after('position');
            }
        });
        
        // Устанавливаем защиту для системных папок (id 1-4)
        // Системные папки должны быть с protected = true (нельзя удалить)
        // user_id будет установлен в следующей миграции
        if (Schema::hasColumn('folders', 'user_id')) {
            DB::table('folders')->whereIn('id', [1, 2, 3, 4])->update([
                'user_id' => null, // Системные папки доступны всем пользователям
                'protected' => true, // Нельзя удалить
                'is_trash' => DB::raw('CASE WHEN id = 4 THEN 1 ELSE 0 END')
            ]);
        } else {
            if (Schema::hasColumn('folders', 'protected') && Schema::hasColumn('folders', 'is_trash')) {
                DB::table('folders')->whereIn('id', [1, 2, 3, 4])->update([
                    'protected' => true, // Нельзя удалить
                    'is_trash' => DB::raw('CASE WHEN id = 4 THEN 1 ELSE 0 END')
                ]);
            }
        }
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
