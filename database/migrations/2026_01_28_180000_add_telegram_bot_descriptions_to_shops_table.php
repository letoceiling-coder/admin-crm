<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Описание бота для страницы до /start (setMyShortDescription, setMyDescription).
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('telegram_bot_short_description', 120)->nullable()->after('telegram_bot_name');
            $table->string('telegram_bot_description', 512)->nullable()->after('telegram_bot_short_description');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['telegram_bot_short_description', 'telegram_bot_description']);
        });
    }
};
