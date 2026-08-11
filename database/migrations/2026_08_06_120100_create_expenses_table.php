<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operational and claimable expenses (BR-613).
 *
 * Routed through the generic Approval Engine (ADR-08) — `approvals` carries the
 * decision, this table carries the expense. The mirrored `status` column exists
 * so listing and filtering never need a join to `approvals`; the engine remains
 * the source of truth for the decision itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();

            // The employee who incurred it. Null for a company-level cost with
            // no individual claimant (rent, software subscriptions).
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->date('expense_date');

            // Unsigned: an expense is a cost, never negative. A refund is a
            // separate record, not a negative expense (ADR-20).
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);

            /*
             * True when the employee paid out of pocket and is owed the money
             * back. A non-claimable expense is a company cost that was already
             * settled directly — it still needs approval and still counts
             * toward the dashboard, but nothing is disbursed to anyone.
             */
            $table->boolean('is_claimable')->default(true);

            $table->string('receipt_path')->nullable();
            $table->string('status', 32)->default('draft');

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'expense_date'], 'expenses_tenant_status_date_index');
            $table->index(['tenant_id', 'employee_id'], 'expenses_tenant_employee_index');
            $table->index(['tenant_id', 'expense_category_id'], 'expenses_tenant_category_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
