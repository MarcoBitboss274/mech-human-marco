<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_supplier_chat_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('operation_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('body')->nullable();

            $table->timestamps();

            $table->index(['operation_id', 'id']);
            $table->index(['operation_id', 'created_at']);

            $table->foreign('operation_id', 'op_supplier_chat_messages_operation_id_foreign')
                ->references('id')
                ->on('operations')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'op_supplier_chat_messages_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_supplier_chat_messages');
    }
};
