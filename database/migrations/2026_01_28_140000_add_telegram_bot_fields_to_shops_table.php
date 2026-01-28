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
        Schema::table('shops', function (Blueprint $table) {
            $table->string('telegram_bot_name')->nullable()->after('telegram_bot_token');
            $table->text('welcome_message')->nullable()->after('telegram_bot_name');
            $table->unsignedBigInteger('welcome_photo_media_id')->nullable()->after('welcome_message');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->foreign('welcome_photo_media_id')->references('id')->on('media')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['welcome_photo_media_id']);
            $table->dropColumn(['telegram_bot_name', 'welcome_message', 'welcome_photo_media_id']);
        });
    }
};
