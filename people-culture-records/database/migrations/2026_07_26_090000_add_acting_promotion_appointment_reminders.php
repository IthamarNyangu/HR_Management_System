<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('temporary_appointments', 'staff_promotion_id')) {
            Schema::table('temporary_appointments', function (Blueprint $table) {
                $table->foreignId('staff_promotion_id')
                    ->nullable()
                    ->after('id')
                    ->unique()
                    ->constrained('staff_promotions')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('temporary_appointment_reminders')) {
            Schema::create('temporary_appointment_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('temporary_appointment_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->unsignedSmallInteger('threshold_days');
                $table->date('scheduled_end_date');
                $table->string('recipient_email', 191);
                $table->string('recipient_role')->nullable();
                $table->timestamp('sent_at');
                $table->timestamps();

                $table->unique(
                    ['temporary_appointment_id', 'threshold_days', 'scheduled_end_date', 'recipient_email'],
                    'temporary_appointment_reminder_unique'
                );
                $table->index(['threshold_days', 'scheduled_end_date'], 'temp_appt_reminder_threshold_end_idx');
            });
        } elseif (! Schema::hasIndex('temporary_appointment_reminders', 'temp_appt_reminder_threshold_end_idx')) {
            Schema::table('temporary_appointment_reminders', function (Blueprint $table) {
                $table->index(['threshold_days', 'scheduled_end_date'], 'temp_appt_reminder_threshold_end_idx');
            });
        }

        $now = now();

        $actingPromotionId = DB::table('promotion_types')->where('code', 'ACTING')->value('id');

        if ($actingPromotionId) {
            DB::table('promotion_types')->where('id', $actingPromotionId)->update([
                'name' => 'Acting Promotion',
                'description' => null,
                'is_active' => true,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('promotion_types')->insert([
                'name' => 'Acting Promotion',
                'code' => 'ACTING',
                'description' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('promotion_types')->updateOrInsert(
            ['code' => 'PERMANENT'],
            [
                'name' => 'Permanent Promotion',
                'description' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('promotion_types')
            ->whereNotIn('code', ['PERMANENT', 'ACTING'])
            ->update(['is_active' => false, 'updated_at' => $now]);

        $relocationReasons = [
            ['name' => 'Employee Request', 'code' => 'EMPLOYEE_REQUEST'],
            ['name' => 'Lateral Movement', 'code' => 'LATERAL_MOVEMENT'],
            ['name' => 'Temporal Movement', 'code' => 'TEMPORAL_MOVEMENT'],
            ['name' => 'Operation Movement', 'code' => 'OPERATION_MOVEMENT'],
            ['name' => 'Amount List', 'code' => 'AMOUNT_LIST'],
        ];

        foreach ($relocationReasons as $reason) {
            $existingId = DB::table('relocation_reasons')
                ->where('name', $reason['name'])
                ->value('id');

            if ($existingId) {
                DB::table('relocation_reasons')->where('id', $existingId)->update([
                    'code' => $reason['code'],
                    'description' => null,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('relocation_reasons')->insert([
                    'name' => $reason['name'],
                    'code' => $reason['code'],
                    'description' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('relocation_reasons')
            ->whereNotIn('code', collect($relocationReasons)->pluck('code')->all())
            ->update(['is_active' => false, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_appointment_reminders');

        Schema::table('temporary_appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_promotion_id');
        });
    }
};
