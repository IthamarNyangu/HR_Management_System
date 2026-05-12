<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_relocations', function (Blueprint $table) {
            $table->timestamp('location_applied_at')->nullable()->after('effective_date');
        });

        DB::table('staff_relocations')
            ->where('update_employee_location', true)
            ->whereNull('location_applied_at')
            ->update(['location_applied_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('staff_relocations', function (Blueprint $table) {
            $table->dropColumn('location_applied_at');
        });
    }
};
