<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('document_type_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->index('document_type_id');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
