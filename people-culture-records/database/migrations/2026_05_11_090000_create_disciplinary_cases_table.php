<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('supervisor_name')->nullable();
            $table->text('nature_of_offence');
            $table->foreignId('offence_category_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('penalty_type_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('case_status_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
            $table->index('province_id');
            $table->index('district_id');
            $table->index('facility_id');
            $table->index('project_id');
            $table->index('offence_category_id');
            $table->index('penalty_type_id');
            $table->index('case_status_id');
            $table->index('effective_date');
            $table->index('expiry_date');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_cases');
    }
};
