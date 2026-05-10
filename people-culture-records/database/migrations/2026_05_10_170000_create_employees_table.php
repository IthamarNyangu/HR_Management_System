<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no')->unique();
            $table->string('first_name')->index();
            $table->string('last_name')->index();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('employment_status_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->date('hire_date')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('province_id');
            $table->index('district_id');
            $table->index('facility_id');
            $table->index('project_id');
            $table->index('department_id');
            $table->index('job_title_id');
            $table->index('employment_status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
