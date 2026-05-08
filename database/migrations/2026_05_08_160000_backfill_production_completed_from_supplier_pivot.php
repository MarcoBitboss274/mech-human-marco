<?php

use App\Enums\ProductionStatusEnum;
use App\Models\Production;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: per le lavorazioni in cui il pivot operation_supplier ha
 * `supplier_completed_at` valorizzato ma la Production associata è rimasta
 * `confirmed` (drift introdotto da una versione precedente del codice in cui
 * `markSupplierProductionCompleted` non aggiornava la Production), porta la
 * Production a `completed` valorizzando `completed_at` dal timestamp del pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('operation_supplier')
            ->where('selected', true)
            ->whereNotNull('supplier_completed_at')
            ->get(['operation_id', 'supplier_completed_at']);

        foreach ($rows as $row) {
            $production = Production::query()
                ->where('operation_id', $row->operation_id)
                ->oldest()
                ->first();

            if (! $production) {
                continue;
            }

            if ($production->status !== ProductionStatusEnum::CONFIRMED->value) {
                continue;
            }

            // Aggiornamento diretto via query builder per evitare l'override
            // del booted hook su completed_at (vogliamo usare il timestamp del pivot).
            DB::table('productions')
                ->where('id', $production->id)
                ->update([
                    'status' => ProductionStatusEnum::COMPLETED->value,
                    'completed_at' => $row->supplier_completed_at,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Migration di backfill puntuale, non reversibile.
    }
};
