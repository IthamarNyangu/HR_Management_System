<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_termination_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('termination_event_uuid');
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('recipient_email', 191);
            $table->string('recipient_name');
            $table->string('recipient_job_title');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(
                ['termination_event_uuid', 'recipient_email'],
                'employee_termination_alert_event_recipient_unique'
            );
            $table->index(['employee_id', 'status'], 'employee_termination_alert_employee_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_termination_alerts');
    }
};
