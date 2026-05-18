<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('temporary_appointment_extensions');

        Schema::create('temporary_appointment_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('temporary_appointment_id');
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->text('reason')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('extended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('extended_at')->nullable();
            $table->timestamps();

            $table->index('temporary_appointment_id');
            $table->index('new_end_date');
            $table->index('extended_at');
            $table->foreign('temporary_appointment_id', 'temp_appt_ext_appt_id_fk')
                ->references('id')
                ->on('temporary_appointments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_appointment_extensions');
    }
};
