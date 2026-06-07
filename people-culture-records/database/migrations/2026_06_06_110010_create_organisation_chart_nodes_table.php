<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_chart_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_chart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('organisation_chart_nodes')->nullOnDelete();
            $table->string('label');
            $table->string('subtitle')->nullable();
            $table->string('node_type')->default('support_unit');
            $table->unsignedInteger('planned_positions')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['organisation_chart_id', 'parent_id', 'sort_order'], 'org_chart_nodes_tree_index');
            $table->index('node_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_chart_nodes');
    }
};
