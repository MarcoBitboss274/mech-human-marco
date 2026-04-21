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
        Schema::table('prescription_prosthesis', function (Blueprint $table) {
            $table->string('crowns_and_bridges_details')->nullable()->after('typology');
            $table->string('full_bridge_details')->nullable()->after('crowns_and_bridges_details');
            $table->json('odontogram')->nullable()->after('full_bridge_details');
            $table->string('3d_normal_model')->nullable()->after('odontogram');
            $table->string('3d_excellent_model')->nullable()->after('3d_normal_model');
            $table->text('note')->nullable()->after('3d_excellent_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescription_prosthesis', function (Blueprint $table) {
            $table->dropColumn([
                'crowns_and_bridges_details',
                'full_bridge_details',
                'odontogram',
                '3d_normal_model',
                '3d_excellent_model',
                'note',
            ]);
        });
    }
};