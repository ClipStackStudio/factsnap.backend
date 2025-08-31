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
        Schema::table('users', function (Blueprint $table) {
            $table->text('push_token')->nullable()->after('phone_verified_at');
            $table->string('platform')->nullable()->after('push_token'); // 'ios', 'android', 'web'
            $table->boolean('is_token_active')->default(true)->after('platform');
            $table->timestamp('token_registered_at')->nullable()->after('is_token_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['push_token', 'platform', 'is_token_active', 'token_registered_at']);
        });
    }
};
