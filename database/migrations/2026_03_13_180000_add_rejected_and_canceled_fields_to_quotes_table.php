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
        Schema::table('quotes', function (Blueprint $table) {
            $table->dateTime('rejected_at')->nullable()->after('notes');
            $table->text('rejected_notes')->nullable()->after('rejected_at');
            $table->dateTime('canceled_at')->nullable()->after('rejected_notes');
            $table->text('canceled_notes')->nullable()->after('canceled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'canceled_notes',
                'canceled_at',
                'rejected_notes',
                'rejected_at',
            ]);
        });
    }
};
