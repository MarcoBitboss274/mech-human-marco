<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role');
            $table->timestamps();

            $table->foreign('supplier_id', 'supplier_user_supplier_id_foreign')
                ->references('id')
                ->on('suppliers')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'supplier_user_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Un utente supplier appartiene a un solo fornitore alla volta.
            $table->unique('user_id', 'supplier_user_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_user');
    }
};
