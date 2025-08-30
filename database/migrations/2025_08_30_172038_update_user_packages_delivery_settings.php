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
            // Drop old columns if they exist
            if (Schema::hasColumn('user_packages', 'delivery_frequency')) {
                $table->dropColumn('delivery_frequency');
            }
            if (Schema::hasColumn('user_packages', 'delivery_time')) {
                $table->dropColumn('delivery_time');
            }
            if (Schema::hasColumn('user_packages', 'delivery_days')) {
                $table->dropColumn('delivery_days');
            }
            if (Schema::hasColumn('user_packages', 'push_enabled')) {
                $table->dropColumn('push_enabled');
            }
        });

        Schema::table('user_packages', function (Blueprint $table) {
            // Add new comprehensive delivery settings
            $table->boolean('delivery_enabled')->default(true)->after('next_delivery_at');
            $table->enum('delivery_mode', ['daily', 'weekly', 'times_per_day', 'windowed'])->default('daily')->after('delivery_enabled');
            $table->json('preferred_times')->nullable()->after('delivery_mode'); // ["10:00", "18:00"]
            $table->json('days_of_week')->nullable()->after('preferred_times'); // [1,2,3,4,5] for weekdays
            $table->time('delivery_window_start')->nullable()->after('days_of_week');
            $table->time('delivery_window_end')->nullable()->after('delivery_window_start');
            $table->smallInteger('per_day_quota')->default(1)->after('delivery_window_end');
            $table->integer('min_interval_minutes')->nullable()->after('per_day_quota');
            $table->boolean('time_sensitive')->default(false)->after('min_interval_minutes');
            $table->timestamp('last_delivered_at')->nullable()->after('time_sensitive');
            $table->uuid('last_selected_fact_id')->nullable()->after('last_delivered_at');
            
            // Add foreign key for last selected fact
            $table->foreign('last_selected_fact_id')->references('id')->on('facts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['last_selected_fact_id']);
            
            // Drop new columns
            $table->dropColumn([
                'delivery_enabled',
                'delivery_mode',
                'preferred_times',
                'days_of_week',
                'delivery_window_start',
                'delivery_window_end',
                'per_day_quota',
                'min_interval_minutes',
                'time_sensitive',
                'last_delivered_at',
                'last_selected_fact_id'
            ]);
        });

        Schema::table('user_packages', function (Blueprint $table) {
            // Restore old columns
            $table->string('delivery_frequency')->default('daily')->after('subscribed_at');
            $table->time('delivery_time')->default('10:00')->after('delivery_frequency');
            $table->json('delivery_days')->nullable()->after('delivery_time');
            $table->boolean('push_enabled')->default(true)->after('next_delivery_at');
        });
    }
};
