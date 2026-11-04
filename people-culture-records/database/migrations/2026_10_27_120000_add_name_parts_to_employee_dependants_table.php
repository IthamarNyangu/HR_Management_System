<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_dependants', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('relationship_detail');
            $table->string('last_name')->nullable()->after('first_name');
        });

        DB::table('employee_dependants')
            ->select(['id', 'full_name'])
            ->orderBy('id')
            ->each(function (object $dependant): void {
                $parts = preg_split('/\s+/', trim((string) $dependant->full_name), 2);

                DB::table('employee_dependants')
                    ->where('id', $dependant->id)
                    ->update([
                        'first_name' => $parts[0] ?? null,
                        'last_name' => $parts[1] ?? null,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('employee_dependants', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
