<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_charts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft');
            $table->date('effective_date')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'project_id']);
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_charts');
    }
};
