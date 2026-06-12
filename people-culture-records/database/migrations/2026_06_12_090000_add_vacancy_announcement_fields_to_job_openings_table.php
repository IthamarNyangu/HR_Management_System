<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->string('contract_duration')->nullable()->after('show_number_of_positions');
            $table->string('job_grade')->nullable()->after('contract_duration');
            $table->foreignId('reporting_to_job_title_id')->nullable()->after('job_grade')->constrained('job_titles')->nullOnDelete();
            $table->boolean('reporting_to_tba')->default(false)->after('reporting_to_job_title_id');

            $table->index('reporting_to_job_title_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropForeign(['reporting_to_job_title_id']);
            $table->dropIndex(['reporting_to_job_title_id']);
            $table->dropColumn([
                'contract_duration',
                'job_grade',
                'reporting_to_job_title_id',
                'reporting_to_tba',
            ]);
        });
    }
};
