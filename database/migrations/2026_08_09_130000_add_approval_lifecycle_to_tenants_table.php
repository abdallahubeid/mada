<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Super Admin approval lifecycle for tenant registrations (ADR-04 amended).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ON `plan_id` VS THE EXISTING `plan` SLUG
 *
 * `tenants.plan` already held the selected plan as a slug string, written at
 * registration and read by SubscriptionOverview. A second column asserting the
 * same fact is how the BR-301 `contract_type`/`pay_basis` divergence happened:
 * two axes, no rule about which wins, and eventually they disagree.
 *
 * So `plan_id` becomes the single source of truth and `plan` is backfilled
 * from it and left in place only as a denormalised read cache for the existing
 * consumers. The backfill below matches on slug; a tenant whose slug matches no
 * plan keeps `plan_id = null`, which the resolver treats as "no plan assigned"
 * rather than guessing.
 *
 * nullOnDelete rather than cascade: deleting a pricing plan must never delete
 * the tenants who were on it.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('plan')
                ->constrained('plans')->nullOnDelete();

            // When the Super Admin approved, who did it, and — on refusal — why.
            $table->timestamp('activated_at')->nullable()->after('renews_at');
            $table->timestamp('reviewed_at')->nullable()->after('activated_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('reviewed_by');

            $table->index(['status', 'created_at'], 'tenants_status_created_index');
        });

        /*
         * Backfill from the slug already stored. Runs as a correlated update
         * rather than in PHP so it stays a single statement regardless of how
         * many tenants exist.
         */
        DB::statement('
            update tenants
            set plan_id = (
                select plans.id from plans
                where plans.slug = tenants.plan
                  and plans.deleted_at is null
                limit 1
            )
            where tenants.plan is not null
        ');

        // Tenants already live pre-date the approval flow; treat their creation
        // as their activation rather than leaving the column null and implying
        // they were never approved.
        DB::table('tenants')
            ->where('status', 'active')
            ->whereNull('activated_at')
            ->update(['activated_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('tenants_status_created_index');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['activated_at', 'reviewed_at', 'rejection_reason']);
        });
    }
};
