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
        Schema::create('operation_supplier', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('status')->nullable();
            $table->boolean('selected')->nullable();
            $table->timestamps();

            $table->unique(['operation_id', 'supplier_id'], 'operation_supplier_operation_id_supplier_id_unique');

            $table->foreign('operation_id', 'operation_supplier_operation_id_foreign')
                ->references('id')
                ->on('operations')
                ->cascadeOnDelete();

            $table->foreign('supplier_id', 'operation_supplier_supplier_id_foreign')
                ->references('id')
                ->on('suppliers')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_supplier');
    }
};
