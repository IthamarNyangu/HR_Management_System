<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->string('visibility');
            $table->string('status');
            $table->unsignedInteger('number_of_positions')->nullable();
            $table->boolean('show_number_of_positions')->default(false);
            $table->longText('description')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('requirements')->nullable();
            $table->longText('qualifications')->nullable();
            $table->longText('experience_required')->nullable();
            $table->text('contract_details')->nullable();
            $table->text('work_level')->nullable();
            $table->text('location_details')->nullable();
            $table->longText('application_instructions')->nullable();
            $table->date('opening_date')->nullable();
            $table->date('closing_date');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reference_no');
            $table->index('slug');
            $table->index('title');
            $table->index('job_title_id');
            $table->index('project_id');
            $table->index('department_id');
            $table->index('province_id');
            $table->index('district_id');
            $table->index('facility_id');
            $table->index('employment_type_id');
            $table->index('visibility');
            $table->index('status');
            $table->index('opening_date');
            $table->index('closing_date');
            $table->index('published_at');
            $table->index('closed_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_openings');
    }
};
