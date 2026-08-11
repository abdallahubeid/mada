<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pay fields on employee contracts (ADR-19, ADR-20, BR-301a/b).
 *
 * `contract_type` stays intact and keeps describing the employment *form*.
 * `pay_basis` is added as an independent axis and is the sole input to pay
 * computation. Neither is ever derived from the other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            // Independent of contract_type — the sole pay-computation input (BR-301).
            $table->string('pay_basis', 16)->default('salaried')->after('contract_type');

            // Minor units (halalas/cents). Unsigned: a rate is never negative (ADR-20).
            // Interpreted through pay_basis: per-period if salaried, per-hour if hourly.
            $table->unsignedBigInteger('base_rate')->default(0)->after('pay_basis');

            // Client-chargeable rate, always distinct from base_rate (BR-604).
            // Reserved for Phase 2B — populated but unused until Invoicing ships.
            $table->unsignedBigInteger('billing_rate')->nullable()->after('base_rate');

            // Frozen at creation from org_settings.currency; never resolved live (BR-301b).
            $table->string('pay_currency', 3)->nullable()->after('billing_rate');

            $table->index(['tenant_id', 'pay_basis'], 'employee_contracts_tenant_pay_basis_index');
        });

        // Freeze each existing contract's currency from its own tenant's settings.
        DB::table('employee_contracts')->whereNull('pay_currency')->update([
            'pay_currency' => DB::raw(
                '(select currency from org_settings
                    where org_settings.tenant_id = employee_contracts.tenant_id
                    limit 1)'
            ),
        ]);

        // Tenants with no org_settings row yet fall back to the platform default.
        DB::table('employee_contracts')->whereNull('pay_currency')->update(['pay_currency' => 'SAR']);
    }

    public function down(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->dropIndex('employee_contracts_tenant_pay_basis_index');
            $table->dropColumn(['pay_basis', 'base_rate', 'billing_rate', 'pay_currency']);
        });
    }
};
