<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funeral_benefit_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('deceased_category');
            $table->string('condition_type');
            $table->decimal('minimum_age_years', 5, 2)->nullable();
            $table->decimal('maximum_age_years', 5, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('funding_source');
            $table->boolean('requires_internal_review')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['deceased_category', 'is_active']);
        });

        Schema::create('funeral_claims', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_dependant_id')->nullable()->constrained('employee_dependants')->nullOnDelete();
            $table->foreignId('province_id')->constrained()->restrictOnDelete();
            $table->string('deceased_category');
            $table->string('claimant_context');
            $table->date('notification_date')->index();
            $table->date('date_of_death')->index();
            $table->string('deceased_name');
            $table->text('deceased_date_of_birth')->nullable();
            $table->decimal('gestational_age_weeks', 5, 2)->nullable();
            $table->decimal('age_at_death_years', 5, 2)->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('eligibility_status')->index();
            $table->text('eligibility_reason');
            $table->foreignId('funeral_benefit_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('eligible_amount', 12, 2)->nullable();
            $table->string('funding_source')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->foreignId('last_status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_status_changed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['province_id', 'status']);
            $table->index(['employee_id', 'deceased_category']);
        });

        Schema::create('funeral_claim_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funeral_claim_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('custom_label')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('status_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();

            $table->index(['funeral_claim_id', 'document_type']);
        });

        Schema::create('funeral_claim_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funeral_claim_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['funeral_claim_id', 'created_at']);
        });

        $now = now();
        DB::table('funeral_benefit_rules')->insert([
            ['code' => 'EMPLOYEE_STANDARD', 'name' => 'Employee Funeral Grant', 'deceased_category' => 'employee', 'condition_type' => 'standard', 'minimum_age_years' => null, 'maximum_age_years' => 70, 'amount' => 30000, 'funding_source' => 'organisational_advance', 'requires_internal_review' => false, 'effective_from' => null, 'effective_to' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'EMPLOYEE_OVER_70', 'name' => 'Employee Over 70 Funeral Grant', 'deceased_category' => 'employee', 'condition_type' => 'over_70', 'minimum_age_years' => 70, 'maximum_age_years' => null, 'amount' => 30000, 'funding_source' => 'organisational_grant', 'requires_internal_review' => true, 'effective_from' => null, 'effective_to' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'SPOUSE_STANDARD', 'name' => 'Spouse Funeral Benefit', 'deceased_category' => 'spouse', 'condition_type' => 'standard', 'minimum_age_years' => null, 'maximum_age_years' => null, 'amount' => 30000, 'funding_source' => 'insurance', 'requires_internal_review' => false, 'effective_from' => null, 'effective_to' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'CHILD_PRETERM', 'name' => 'Preterm Child Funeral Benefit', 'deceased_category' => 'child', 'condition_type' => 'gestational', 'minimum_age_years' => null, 'maximum_age_years' => null, 'amount' => 7500, 'funding_source' => 'insurance', 'requires_internal_review' => false, 'effective_from' => null, 'effective_to' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'CHILD_0_6', 'name' => 'Child Funeral Benefit - Birth to 6 Years', 'deceased_category' => 'child', 'condition_type' => 'age_range', 'minimum_age_years' => 0, 'maximum_age_years' => 6, 'amount' => 7500, 'funding_source' => 'insurance', 'requires_internal_review' => false, 'effective_from' => null, 'effective_to' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'CHILD_7_21', 'name' => 'Child Funeral Benefit - 7 to 21 Years', 'deceased_category' => 'child', 'condition_type' => 'age_range', 'minimum_age_years' => 7, 'maximum_age_years' => 21, 'amount' => 15000, 'funding_source' => 'insurance', 'requires_internal_review' => false, 'effective_from' => null, 'effective_to' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('funeral_claim_status_histories');
        Schema::dropIfExists('funeral_claim_documents');
        Schema::dropIfExists('funeral_claims');
        Schema::dropIfExists('funeral_benefit_rules');
    }
};
