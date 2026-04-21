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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('expire_at');
            $table->string('address')->nullable()->after('company_name');
            $table->string('city')->nullable()->after('address');
            $table->string('province')->nullable()->after('city');
            $table->string('cap')->nullable()->after('province');
            $table->text('notes')->nullable()->after('cap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'address',
                'city',
                'province',
                'cap',
                'notes',
            ]);
        });
    }
};
