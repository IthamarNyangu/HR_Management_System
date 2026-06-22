<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_openings', function (Blueprint $table) {
            $table->unsignedInteger('advertisement_round')->default(1)->after('reference_no');
            $table->index('advertisement_round');
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->unsignedInteger('advertisement_round')->default(1)->after('job_opening_id');
            $table->index(['job_opening_id', 'advertisement_round', 'email'], 'job_applications_round_email_index');
        });

        Schema::create('job_opening_readvertisement_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_opening_id');
            $table->unsignedBigInteger('job_application_id');
            $table->unsignedInteger('advertisement_round');
            $table->string('recipient_email');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->foreign('job_opening_id', 'job_readvert_notice_opening_fk')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->foreign('job_application_id', 'job_readvert_notice_application_fk')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->foreign('sent_by', 'job_readvert_notice_sender_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['job_opening_id', 'job_application_id', 'advertisement_round'], 'job_opening_round_application_unique');
            $table->index(['job_opening_id', 'advertisement_round'], 'job_opening_round_notification_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_opening_readvertisement_notifications');

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex('job_applications_round_email_index');
            $table->dropColumn('advertisement_round');
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropIndex(['advertisement_round']);
            $table->dropColumn('advertisement_round');
        });
    }
};
