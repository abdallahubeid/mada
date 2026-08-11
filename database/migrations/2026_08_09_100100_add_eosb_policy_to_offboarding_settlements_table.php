<?php

use App\Domain\Finance\Support\EosbPolicy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot the EOSB rules a settlement was computed under.
 *
 * Every other figure on this table is already frozen at generation (BR-608).
 * The RULES were not, because until `finance_settings` existed they could not
 * change. Now that a tenant can edit them, an approved settlement would
 * otherwise become unexplainable the moment someone adjusts a rate — the
 * number on the locked record would no longer reconcile against the rules the
 * screen displays.
 *
 * Nullable: rows generated before this column existed keep rendering, and read
 * back as {@see EosbPolicy::default()}, which is
 * what they were in fact computed under.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offboarding_settlements', function (Blueprint $table) {
            $table->json('eosb_policy')->nullable()->after('unused_leave_days');
        });
    }

    public function down(): void
    {
        Schema::table('offboarding_settlements', function (Blueprint $table) {
            $table->dropColumn('eosb_policy');
        });
    }
};
