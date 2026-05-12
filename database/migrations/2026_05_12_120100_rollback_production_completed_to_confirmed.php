<?php

use App\Enums\ProductionStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rollback dei `Production.status = completed` impostati dal vecchio flusso fornitore
 * (markSupplierProductionCompleted): nel nuovo modello "completed" è una transizione
 * esclusiva dell'Admin M&H. Riporta tutto a `confirmed` con `completed_at = null`.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('productions')
            ->where('status', ProductionStatusEnum::COMPLETED->value)
            ->update([
                'status' => ProductionStatusEnum::CONFIRMED->value,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Rollback dati non reversibile: l'informazione originale (chi aveva completato cosa)
        // è già preservata in `operation_supplier.supplier_completed_at`.
    }
};
