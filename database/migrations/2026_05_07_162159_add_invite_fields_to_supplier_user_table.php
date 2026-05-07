<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_user', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_user', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('role');
            }
            if (! Schema::hasColumn('supplier_user', 'invite_token')) {
                $table->string('invite_token')->nullable()->after('accepted_at');
            }
            if (! Schema::hasColumn('supplier_user', 'is_new')) {
                $table->boolean('is_new')->default(false)->after('invite_token');
            }
        });

        // Backfill: gli utenti con last_login_at != null sono già "accettati" — popoliamo
        // accepted_at dal first login (solo per le righe ancora vuote).
        DB::statement('UPDATE supplier_user
            SET accepted_at = (SELECT users.last_login_at FROM users WHERE users.id = supplier_user.user_id)
            WHERE supplier_user.accepted_at IS NULL
              AND (SELECT users.last_login_at FROM users WHERE users.id = supplier_user.user_id) IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('supplier_user', function (Blueprint $table) {
            $table->dropColumn(['accepted_at', 'invite_token', 'is_new']);
        });
    }
};
