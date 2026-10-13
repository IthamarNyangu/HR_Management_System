<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('funeral_claim_documents')
            ->where('document_type', 'certified_nrc')
            ->update(['is_required' => false]);
    }

    public function down(): void
    {
        DB::table('funeral_claim_documents')
            ->where('document_type', 'certified_nrc')
            ->update(['is_required' => true]);
    }
};
