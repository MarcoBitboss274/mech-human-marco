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
        Schema::table('prescription_semi_finished_prostheses', function (Blueprint $table) {
            $table->string('crowns_and_bridges_details')->nullable()->after('typology');
            $table->string('full_bridge_details')->nullable()->after('crowns_and_bridges_details');
            $table->json('odontogram')->nullable()->after('full_bridge_details');
            $table->text('note')->nullable()->after('odontogram');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescription_semi_finished_prostheses', function (Blueprint $table) {
            $table->dropColumn([
                'crowns_and_bridges_details',
                'full_bridge_details',
                'odontogram',
                'note',
            ]);
        });
    }
};