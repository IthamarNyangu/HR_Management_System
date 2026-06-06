<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termination_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('termination_reason_id')
                ->nullable()
                ->after('employment_status_id')
                ->constrained('termination_reasons')
                ->nullOnDelete();
            $table->date('termination_date')->nullable()->after('termination_reason_id')->index();
            $table->text('termination_comment')->nullable()->after('termination_date');
        });

        $now = now();

        foreach ([
            ['name' => 'Resignation', 'code' => 'RESIGNATION'],
            ['name' => 'End of Contract', 'code' => 'END_OF_CONTRACT'],
            ['name' => 'Deceased', 'code' => 'DECEASED'],
            ['name' => 'Redundancy', 'code' => 'REDUNDANCY'],
            ['name' => 'Dismissed', 'code' => 'DISMISSED'],
            ['name' => 'Discharged', 'code' => 'DISCHARGED'],
            ['name' => 'Ill Health', 'code' => 'ILL_HEALTH'],
        ] as $reason) {
            DB::table('termination_reasons')->updateOrInsert(
                ['name' => $reason['name']],
                [
                    'code' => $reason['code'],
                    'description' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        foreach ([
            ['name' => 'Active', 'code' => 'ACTIVE'],
            ['name' => 'Terminated', 'code' => 'TERMINATED'],
        ] as $status) {
            DB::table('employment_statuses')->updateOrInsert(
                ['name' => $status['name']],
                [
                    'code' => $status['code'],
                    'description' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        DB::table('employment_statuses')
            ->whereNotIn('name', ['Active', 'Terminated'])
            ->update(['is_active' => false, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('termination_reason_id');
            $table->dropColumn(['termination_date', 'termination_comment']);
        });

        Schema::dropIfExists('termination_reasons');
    }
};
