<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_dependants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('category');
            $table->string('relationship_detail')->nullable();
            $table->string('full_name');
            $table->string('gender', 50)->nullable();
            $table->text('date_of_birth')->nullable();
            $table->text('national_id')->nullable();
            $table->date('relationship_start_date')->nullable();
            $table->date('relationship_end_date')->nullable();
            $table->string('verification_status')->default('unverified');
            $table->boolean('is_active')->default(true);
            $table->text('internal_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'is_active']);
            $table->index(['employee_id', 'verification_status']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_dependants');
    }
};
