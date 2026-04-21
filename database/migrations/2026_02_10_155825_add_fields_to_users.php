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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('odontoiatra')->default(false)->after('email_verified_at');
            $table->boolean('odontotecnico')->default(false)->after('odontoiatra');
            $table->string('roll_number')->nullable()->after('odontotecnico');
            $table->string('roll_province')->nullable()->after('roll_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('odontoiatra');
            $table->dropColumn('odontotecnico');
            $table->dropColumn('roll_number');
            $table->dropColumn('roll_province');
        });
    }
};