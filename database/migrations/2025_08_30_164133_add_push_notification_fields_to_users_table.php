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
            $table->string('apns_device_token')->nullable()->after('last_logged_in_at');
            $table->json('notification_preferences')->nullable()->after('apns_device_token');
            $table->boolean('push_notifications_enabled')->default(true)->after('notification_preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['apns_device_token', 'notification_preferences', 'push_notifications_enabled']);
        });
    }
};
