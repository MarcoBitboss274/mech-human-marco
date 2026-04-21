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
        Schema::table('prescription_guided_surgery', function (Blueprint $table) {
            $table->json('odontogram')->nullable()->after('surgery_typology');
            $table->string('desired_implant_line')->nullable()->after('odontogram');
            $table->text('additional_info')->nullable()->after('desired_implant_line');
            $table->text('note')->nullable()->after('additional_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescription_guided_surgery', function (Blueprint $table) {
            $table->dropColumn([
                'odontogram',
                'desired_implant_line',
                'additional_info',
                'note',
            ]);
        });
    }
};