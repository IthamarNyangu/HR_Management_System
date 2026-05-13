<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable()->unique();
            $table->string('import_type');
            $table->string('original_filename');
            $table->string('stored_filename')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('uploaded');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->json('error_summary')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['import_type', 'status']);
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
