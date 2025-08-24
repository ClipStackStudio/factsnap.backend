<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->unique()->after('id');
            $table->string('phone_number')->nullable()->unique()->after('firebase_uid');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop unique indexes first using conventional names
            $table->dropUnique('users_firebase_uid_unique');
            $table->dropUnique('users_phone_number_unique');
            $table->dropColumn(['firebase_uid', 'phone_number', 'phone_verified_at']);
        });
    }
};
