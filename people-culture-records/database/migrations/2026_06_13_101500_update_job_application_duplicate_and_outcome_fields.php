<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropUnique('job_applications_job_opening_id_email_unique');
            $table->index(['job_opening_id', 'email'], 'job_applications_job_opening_email_index');
            $table->timestamp('outcome_sent_at')->nullable()->after('last_confirmation_sent_at');
            $table->foreignId('outcome_sent_by')->nullable()->after('outcome_sent_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropForeign(['outcome_sent_by']);
            $table->dropColumn(['outcome_sent_at', 'outcome_sent_by']);
            $table->dropIndex('job_applications_job_opening_email_index');
            $table->unique(['job_opening_id', 'email']);
        });
    }
};
