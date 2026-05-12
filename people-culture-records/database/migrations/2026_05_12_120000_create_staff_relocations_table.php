<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_relocations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('from_province_id')->constrained('provinces');
            $table->foreignId('from_district_id')->constrained('districts');
            $table->foreignId('from_facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->foreignId('to_province_id')->constrained('provinces');
            $table->foreignId('to_district_id')->constrained('districts');
            $table->foreignId('to_facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->foreignId('relocation_reason_id')->nullable()->constrained('relocation_reasons')->nullOnDelete();
            $table->date('effective_date');
            $table->decimal('relocation_amount', 12, 2)->nullable();
            $table->text('comment')->nullable();
            $table->boolean('update_employee_location')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('effective_date');
            $table->index('relocation_amount');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_relocations');
    }
};
