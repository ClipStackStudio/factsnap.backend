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
            $table->dropPrimary(); // Remove the primary key constraint first
            $table->dropColumn('id'); // Drop the id column
            $table->primary(['user_id', 'package_id']); // Make composite primary key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropPrimary(); // Remove composite primary key
            $table->uuid('id')->primary()->first(); // Add back the id column as primary key
        });
    }
};
