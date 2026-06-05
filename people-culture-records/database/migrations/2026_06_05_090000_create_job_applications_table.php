<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('source')->default('external');
            $table->string('status')->default('submitted');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('highest_qualification')->nullable();
            $table->string('field_of_study')->nullable();
            $table->decimal('years_of_experience', 4, 1)->nullable();
            $table->string('current_employer')->nullable();
            $table->longText('motivation')->nullable();
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('withdrawal_token_hash')->nullable();
            $table->timestamp('last_confirmation_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['job_opening_id', 'email']);
            $table->index('job_opening_id');
            $table->index('employee_id');
            $table->index('source');
            $table->index('status');
            $table->index('email');
            $table->index('submitted_at');
            $table->index('withdrawn_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
