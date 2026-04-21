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
        Schema::table('prescription_3d_mesh', function (Blueprint $table) {
            $table->json('odontogram')->nullable()->after('dimension');
            $table->string('outer_finish')->nullable()->after('odontogram');
            $table->string('inner_finish')->nullable()->after('outer_finish');
            $table->string('pattern')->nullable()->after('inner_finish');
            $table->string('stress_breakers')->nullable()->after('pattern');
            $table->string('3d_model')->nullable()->after('stress_breakers');
            $table->double('screw_diameter')->nullable()->after('3d_model');
            $table->text('shared_project_note')->nullable()->after('screw_diameter');
            $table->text('note')->nullable()->after('shared_project_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescription_3d_mesh', function (Blueprint $table) {
            $table->dropColumn([
                'odontogram',
                'outer_finish',
                'inner_finish',
                'pattern',
                'stress_breakers',
                '3d_model',
                'screw_diameter',
                'shared_project_note',
                'note',
            ]);
        });
    }
};