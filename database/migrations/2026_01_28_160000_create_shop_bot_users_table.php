<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Пользователи бота магазина (Telegram): привязаны к shop_id, создаются при /start.
     */
    public function up(): void
    {
        Schema::create('shop_bot_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('telegram_user_id');
            $table->unsignedBigInteger('telegram_chat_id');
            $table->string('username', 255)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('language_code', 10)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'telegram_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_bot_users');
    }
};
