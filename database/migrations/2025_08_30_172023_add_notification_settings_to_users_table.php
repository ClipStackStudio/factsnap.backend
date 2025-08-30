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
            $table->string('timezone')->default('UTC')->after('push_notifications_enabled');
            $table->time('quiet_hours_start')->default('22:00')->after('timezone');
            $table->time('quiet_hours_end')->default('08:00')->after('quiet_hours_start');
            $table->boolean('quiet_hours_enabled')->default(true)->after('quiet_hours_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'quiet_hours_start',
                'quiet_hours_end',
                'quiet_hours_enabled'
            ]);
        });
    }
};
