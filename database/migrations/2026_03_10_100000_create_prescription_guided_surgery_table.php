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
        Schema::create('prescription_guided_surgery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->string('surgery_typology')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prescription_id', 'prescription_guided_surgery_prescription_id_foreign')
                ->references('id')
                ->on('prescriptions')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_guided_surgery');
    }
};
