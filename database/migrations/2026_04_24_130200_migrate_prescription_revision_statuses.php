<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('prescriptions')->where('status', 'in_review')->update(['status' => 'sent']);
        DB::table('prescriptions')->where('status', 'revised')->update(['status' => 'confirmed']);
    }

    public function down(): void
    {
        // Non reversibile: lo stato originale non è ricostruibile.
    }
};
