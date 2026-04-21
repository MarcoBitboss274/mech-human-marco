<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prescription_lybra_aligner', function (Blueprint $table) {
            $table->text('note')->nullable()->after('cut_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescription_lybra_aligner', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};