<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->decimal('qualification_score', 5, 2)->nullable()->after('last_confirmation_sent_at');
            $table->decimal('experience_score', 5, 2)->nullable()->after('qualification_score');
            $table->decimal('screening_score', 5, 2)->nullable()->after('experience_score');
            $table->decimal('overall_score', 5, 2)->nullable()->after('screening_score');
            $table->longText('review_notes')->nullable()->after('overall_score');
            $table->foreignId('reviewed_by')->nullable()->after('review_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('shortlisted_at')->nullable()->after('reviewed_at');
            $table->timestamp('rejected_at')->nullable()->after('shortlisted_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->foreignId('last_status_changed_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('last_status_changed_at')->nullable()->after('last_status_changed_by');

            $table->index('overall_score');
            $table->index('reviewed_at');
            $table->index('shortlisted_at');
            $table->index('rejected_at');
        });

        DB::table('job_applications')
            ->where('status', 'not_progressed')
            ->update([
                'status' => 'rejected',
                'rejected_at' => DB::raw('COALESCE(outcome_sent_at, updated_at)'),
                'last_status_changed_at' => DB::raw('COALESCE(outcome_sent_at, updated_at)'),
                'last_status_changed_by' => DB::raw('outcome_sent_by'),
            ]);

        Schema::create('job_application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamps();

            $table->index('job_application_id');
            $table->index('to_status');
            $table->index('created_at');
        });

        Schema::create('job_application_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->boolean('is_private')->default(true);
            $table->timestamps();

            $table->index('job_application_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_application_notes');
        Schema::dropIfExists('job_application_status_histories');

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['last_status_changed_by']);
            $table->dropIndex(['overall_score']);
            $table->dropIndex(['reviewed_at']);
            $table->dropIndex(['shortlisted_at']);
            $table->dropIndex(['rejected_at']);
            $table->dropColumn([
                'qualification_score',
                'experience_score',
                'screening_score',
                'overall_score',
                'review_notes',
                'reviewed_by',
                'reviewed_at',
                'shortlisted_at',
                'rejected_at',
                'rejection_reason',
                'last_status_changed_by',
                'last_status_changed_at',
            ]);
        });
    }
};
