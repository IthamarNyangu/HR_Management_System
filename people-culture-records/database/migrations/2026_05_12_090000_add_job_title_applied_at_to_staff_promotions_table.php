<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_promotions', function (Blueprint $table) {
            $table->timestamp('job_title_applied_at')->nullable()->after('effective_date');
        });

        DB::table('staff_promotions')
            ->where('update_employee_job_title', true)
            ->whereNull('job_title_applied_at')
            ->update(['job_title_applied_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('staff_promotions', function (Blueprint $table) {
            $table->dropColumn('job_title_applied_at');
        });
    }
};
