<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_establishment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->string('title');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft');
            $table->date('effective_month');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'project_id']);
            $table->index('effective_month');
        });

        Schema::create('staff_establishment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_establishment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_title_id')->constrained()->restrictOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('budgeted_positions')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('staff_establishment_plan_id');
            $table->index('job_title_id');
            $table->index('province_id');
            $table->index('district_id');
            $table->index('facility_id');
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_establishment_lines');
        Schema::dropIfExists('staff_establishment_plans');
    }
};
