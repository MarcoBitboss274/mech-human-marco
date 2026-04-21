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
        Schema::table('prescription_protrusor', function (Blueprint $table) {
            $table->json('odontogram')->nullable()->after('protrusor_typology');
            $table->string('jig')->nullable()->after('odontogram');
            $table->string('remaining_upper_teeth')->nullable()->after('jig');
            $table->string('remaining_lower_teeth')->nullable()->after('remaining_upper_teeth');
            $table->string('transpalatal_arch')->nullable()->after('remaining_lower_teeth');
            $table->double('mandibular_advancement')->nullable()->after('transpalatal_arch');
            $table->double('mandibular_advancement_2')->nullable()->after('mandibular_advancement');
            $table->text('note')->nullable()->after('mandibular_advancement_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescription_protrusor', function (Blueprint $table) {
            $table->dropColumn([
                'odontogram',
                'jig',
                'remaining_upper_teeth',
                'remaining_lower_teeth',
                'transpalatal_arch',
                'mandibular_advancement',
                'mandibular_advancement_2',
                'note',
            ]);
        });
    }
};