<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_offboarding_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->uuid('integration_request_uuid')->unique();
            $table->unsignedBigInteger('exitclear_case_id')->nullable()->unique();
            $table->string('case_number', 40)->nullable()->index();
            $table->string('status', 30)->default('initiating')->index();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_offboarding_cases');
    }
};
