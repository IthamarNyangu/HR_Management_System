<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funeral_claims', function (Blueprint $table) {
            $table->boolean('family_meeting_required')->default(false)->after('internal_notes');
            $table->string('family_meeting_status')->default('not_required')->after('family_meeting_required');
            $table->dateTime('family_meeting_scheduled_at')->nullable()->after('family_meeting_status');
            $table->dateTime('family_meeting_completed_at')->nullable()->after('family_meeting_scheduled_at');
            $table->text('family_meeting_outcome')->nullable()->after('family_meeting_completed_at');
            $table->boolean('grant_consent_confirmed')->default(false)->after('family_meeting_outcome');
            $table->date('grant_consent_date')->nullable()->after('grant_consent_confirmed');
            $table->boolean('banking_details_verified')->default(false)->after('grant_consent_date');
            $table->foreignId('banking_details_verified_by')->nullable()->after('banking_details_verified')->constrained('users')->nullOnDelete();
            $table->timestamp('banking_details_verified_at')->nullable()->after('banking_details_verified_by');
            $table->string('payreq_reference')->nullable()->after('banking_details_verified_at');
            $table->date('payreq_raised_date')->nullable()->after('payreq_reference');
            $table->string('payreq_status')->default('not_raised')->after('payreq_raised_date');
            $table->date('payreq_approved_date')->nullable()->after('payreq_status');
            $table->foreignId('approved_by')->nullable()->after('payreq_approved_date')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_comment')->nullable()->after('approved_at');
            $table->decimal('grant_amount_approved', 12, 2)->nullable()->after('approval_comment');
            $table->string('payment_status')->default('not_paid')->after('grant_amount_approved');
            $table->date('payment_date')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_date');
            $table->date('family_payment_confirmed_date')->nullable()->after('payment_reference');
            $table->foreignId('reopened_by')->nullable()->after('family_payment_confirmed_date')->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('reopened_by');
            $table->text('reopen_reason')->nullable()->after('reopened_at');

            $table->index(['payreq_status', 'payment_status']);
        });

        Schema::create('funeral_claim_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funeral_claim_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['funeral_claim_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funeral_claim_workflow_events');

        Schema::table('funeral_claims', function (Blueprint $table) {
            $table->dropIndex(['payreq_status', 'payment_status']);
            $table->dropConstrainedForeignId('banking_details_verified_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn([
                'family_meeting_required', 'family_meeting_status', 'family_meeting_scheduled_at',
                'family_meeting_completed_at', 'family_meeting_outcome', 'grant_consent_confirmed',
                'grant_consent_date', 'banking_details_verified', 'banking_details_verified_at',
                'payreq_reference', 'payreq_raised_date', 'payreq_status', 'payreq_approved_date',
                'approved_at', 'approval_comment', 'grant_amount_approved', 'payment_status',
                'payment_date', 'payment_reference', 'family_payment_confirmed_date', 'reopened_at',
                'reopen_reason',
            ]);
        });
    }
};
