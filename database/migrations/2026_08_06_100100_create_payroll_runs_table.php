<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll run — the maker-checker unit of work (BR-603, ADR-09).
 *
 * States: draft -> pending_approval -> approved (locked) -> paid.
 * Rejection returns to draft with a reason. Once approved the run and every
 * payslip under it are immutable (BR-610); corrections are adjustment entries
 * in a subsequent run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('period', 7);                 // YYYY-MM
            $table->date('period_start');
            $table->date('period_end');

            $table->string('status', 32)->default('draft');

            // Frozen at open — never read live from org_settings (BR-301b).
            $table->string('currency', 3);

            $table->foreignId('maker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            // Snapshot totals in signed minor units (ADR-20). Signed because a
            // net can legitimately land below zero once deductions exceed gross.
            $table->bigInteger('total_base')->default(0);
            $table->bigInteger('total_absence_deduction')->default(0);
            $table->bigInteger('total_allowances')->default(0);
            $table->bigInteger('total_deductions')->default(0);
            $table->bigInteger('total_gross')->default(0);
            $table->bigInteger('total_net')->default(0);
            $table->unsignedInteger('payslip_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            /*
             * BR-611: at most one LIVE run per (tenant, period).
             *
             * A plain unique on (tenant_id, period) would permanently burn the
             * period once a draft is cancelled or soft-deleted. Putting
             * deleted_at in the key does not work either — MySQL/MariaDB treat
             * NULLs as distinct, so two live rows would both pass.
             *
             * This stored generated column mirrors `period` only while the run
             * is live, and is NULL otherwise. NULLs being distinct is exactly
             * what we want here: any number of cancelled/deleted runs may
             * coexist, but only one live run can hold a given period.
             */
            $table->string('active_period', 7)
                ->storedAs("if(deleted_at is null and status <> 'cancelled', period, null)");

            $table->unique(['tenant_id', 'active_period'], 'payroll_runs_tenant_active_period_unique');
            $table->index(['tenant_id', 'period', 'status'], 'payroll_runs_tenant_period_status_index');
            $table->index(['tenant_id', 'status'], 'payroll_runs_tenant_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
