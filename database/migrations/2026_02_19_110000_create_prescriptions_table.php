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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('typology')->nullable();
            $table->string('ref')->nullable();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->string('gender')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('operation_id', 'prescriptions_operation_id_foreign')
                ->references('id')
                ->on('operations')
                ->cascadeOnDelete();

            $table->foreign('building_id', 'prescriptions_building_id_foreign')
                ->references('id')
                ->on('buildings')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'prescriptions_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
