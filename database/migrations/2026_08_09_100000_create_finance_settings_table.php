<?php

use App\Domain\Finance\Support\EosbPolicy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant finance configuration — currently the end-of-service rules.
 *
 * A tenant-scoped SINGLETON, same shape as `org_settings`: one row per tenant,
 * enforced by a unique key on `tenant_id`, and absent rather than pre-seeded
 * until someone opens the settings screen. A tenant with no row computes on
 * {@see EosbPolicy::default()}, which reproduces
 * the constants this table replaced.
 *
 * Rates are BASIS POINTS, not money — 5_000 bps = 50%. Stored as integers for
 * the same reason money is (ADR-20): no float ever participates in a figure
 * that ends up on a settlement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            /*
             * ---- End-of-service benefit (EOSB) ----
             * Defaults are the GCC/Saudi pattern the module shipped with. They
             * are DEFAULTS, not law: each tenant confirms them against the
             * statute that applies to it.
             */
            $table->boolean('eosb_enabled')->default(true);
            $table->unsignedSmallInteger('eosb_tier_boundary_months')->default(60);
            $table->unsignedInteger('eosb_lower_tier_bps')->default(5000);
            $table->unsignedInteger('eosb_upper_tier_bps')->default(10000);

            /*
             * Resignation taper: [{months, bps}, ...] ascending. JSON rather
             * than columns because the number of bands is jurisdictional — the
             * Saudi pattern has three, others have one or none.
             */
            $table->json('eosb_resignation_taper')->nullable();

            /*
             * The notional working month. Used to derive an hourly employee's
             * monthly wage (no payslip history is required at settlement time)
             * and as the leave-payout divisor when the final month has no
             * scheduled days in the Work Ledger.
             */
            $table->unsignedSmallInteger('nominal_month_days')->default(22);
            $table->unsignedSmallInteger('nominal_day_hours')->default(8);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_settings');
    }
};
