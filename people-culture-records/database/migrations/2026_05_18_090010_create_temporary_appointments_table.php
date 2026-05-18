<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('current_job_title_id')->nullable()->constrained('job_titles')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('temporary_job_title_id')->constrained('job_titles')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('appointment_type_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('appointment_status_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->text('reason')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
            $table->index('province_id');
            $table->index('district_id');
            $table->index('facility_id');
            $table->index('project_id');
            $table->index('department_id');
            $table->index('current_job_title_id');
            $table->index('temporary_job_title_id');
            $table->index('appointment_type_id');
            $table->index('appointment_status_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_appointments');
    }
};
