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
        Schema::table('buildings', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_id')->nullable()->after('id');

            $table->foreign('agent_id', 'buildings_agent_id_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('agent_id', 'buildings_agent_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropIndex('buildings_agent_id_index');
            $table->dropForeign('buildings_agent_id_foreign');
            $table->dropColumn('agent_id');
        });
    }
};

