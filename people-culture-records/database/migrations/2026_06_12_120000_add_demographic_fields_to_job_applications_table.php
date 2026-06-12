<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('title')->nullable()->after('status');
            $table->string('gender')->nullable()->after('national_id');
            $table->string('disability')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['title', 'gender', 'disability']);
        });
    }
};
