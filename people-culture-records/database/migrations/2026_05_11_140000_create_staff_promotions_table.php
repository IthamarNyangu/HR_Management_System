<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('province_id')->constrained('provinces');
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('old_job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->foreignId('new_job_title_id')->constrained('job_titles');
            $table->foreignId('promotion_type_id')->nullable()->constrained('promotion_types')->nullOnDelete();
            $table->date('promotion_date');
            $table->date('effective_date')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('update_employee_job_title')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('promotion_date');
            $table->index('effective_date');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_promotions');
    }
};
