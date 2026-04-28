<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->foreignId('opened_by')->constrained('users');
            $table->timestamp('last_submitted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['prescription_id', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
