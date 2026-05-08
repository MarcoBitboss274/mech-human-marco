<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operation_supplier', function (Blueprint $table) {
            $table->timestamp('supplier_completed_at')->nullable()->after('selected_at');
        });
    }

    public function down(): void
    {
        Schema::table('operation_supplier', function (Blueprint $table) {
            $table->dropColumn('supplier_completed_at');
        });
    }
};
