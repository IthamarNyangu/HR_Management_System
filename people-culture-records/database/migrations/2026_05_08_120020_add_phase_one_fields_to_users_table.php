<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->after('role_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('province_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'province_id', 'is_active']);
        });
    }
};
