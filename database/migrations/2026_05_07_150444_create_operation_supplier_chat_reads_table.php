<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_supplier_chat_reads', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('operation_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->unique(['operation_id', 'user_id'], 'op_supplier_chat_reads_operation_user_unique');
            $table->index(['user_id', 'operation_id']);

            $table->foreign('operation_id', 'op_supplier_chat_reads_operation_id_foreign')
                ->references('id')
                ->on('operations')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'op_supplier_chat_reads_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('last_read_message_id', 'op_supplier_chat_reads_last_message_id_foreign')
                ->references('id')
                ->on('operation_supplier_chat_messages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_supplier_chat_reads');
    }
};
