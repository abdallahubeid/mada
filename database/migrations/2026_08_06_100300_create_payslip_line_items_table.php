<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Typed allowance/deduction rows on a payslip (BR-601).
 *
 * `label` and `kind` are snapshots of the type at the moment the line was
 * created, so renaming or deactivating a line item type later cannot alter a
 * locked payslip (BR-608).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_line_item_type_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot of the type at creation time.
            $table->string('label');
            $table->string('kind', 16);

            // Signed minor units, effect on net pay: allowance > 0, deduction < 0.
            $table->bigInteger('amount');

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'payslip_id'], 'payslip_line_items_tenant_payslip_index');
            $table->index(['tenant_id', 'kind'], 'payslip_line_items_tenant_kind_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_line_items');
    }
};
