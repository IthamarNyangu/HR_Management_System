<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->string('status')->default('pending');
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->foreignId('matched_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
            $table->index('row_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
