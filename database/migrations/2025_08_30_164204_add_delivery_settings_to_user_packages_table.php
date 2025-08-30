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
        Schema::table('user_packages', function (Blueprint $table) {
            $table->string('delivery_frequency')->default('daily')->after('subscribed_at'); // daily|weekly|custom
            $table->time('delivery_time')->default('09:00:00')->after('delivery_frequency'); // Time of day
            $table->json('delivery_days')->nullable()->after('delivery_time'); // For weekly: [1,2,3,4,5] (Mon-Fri)
            $table->timestamp('next_delivery_at')->nullable()->after('delivery_days');
            $table->boolean('push_enabled')->default(true)->after('next_delivery_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn(['delivery_frequency', 'delivery_time', 'delivery_days', 'next_delivery_at', 'push_enabled']);
        });
    }
};
