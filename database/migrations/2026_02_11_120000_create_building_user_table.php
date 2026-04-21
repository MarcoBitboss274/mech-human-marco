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
        Schema::create('building_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->foreign('building_id', 'building_user_building_id_foreign')
                ->references('id')
                ->on('buildings')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'building_user_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique(['building_id', 'user_id'], 'building_user_building_id_user_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_user');
    }
};
