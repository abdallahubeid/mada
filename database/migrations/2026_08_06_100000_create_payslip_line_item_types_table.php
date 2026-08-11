<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant-configurable allowance/deduction definitions (BR-601).
 *
 * These are DATA, not an application enum, so a tenant can add "housing
 * allowance" or "GOSI deduction" without a code change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_line_item_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();

            // allowance | deduction — grouping/display metadata and the sign guard.
            $table->string('kind', 16);

            // Signed minor units, expressed as effect on net pay (ADR-20).
            $table->bigInteger('default_amount')->default(0);

            $table->boolean('is_active')->default(true);

            // Reserved for Phase 2B tax work — inert until then (ADR-22).
            $table->boolean('is_taxable')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'payslip_line_item_types_tenant_code_unique');
            $table->index(['tenant_id', 'kind', 'is_active'], 'payslip_line_item_types_tenant_kind_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_line_item_types');
    }
};
