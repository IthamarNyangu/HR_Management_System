<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_opening_province', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['job_opening_id', 'province_id']);
            $table->index('province_id');
        });

        DB::table('job_openings')
            ->whereNotNull('province_id')
            ->orderBy('id')
            ->get(['id', 'province_id', 'created_at', 'updated_at'])
            ->each(function ($jobOpening): void {
                DB::table('job_opening_province')->insertOrIgnore([
                    'job_opening_id' => $jobOpening->id,
                    'province_id' => $jobOpening->province_id,
                    'created_at' => $jobOpening->created_at ?? now(),
                    'updated_at' => $jobOpening->updated_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_opening_province');
    }
};
