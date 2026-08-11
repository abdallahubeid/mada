<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ONLY legal correction path for a locked payroll run (BR-603, NFR-11).
 *
 * An adjustment never touches the run it corrects. It is recorded against a
 * LATER run and references the original payslip, so the locked period keeps
 * rendering exactly what was approved while the money moves in the next cycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // The run that CARRIES the correction (a draft in a later period).
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();

            // The locked payslip being corrected. Nullified rather than cascaded
            // so an adjustment survives as an audit trace even if the original
            // is ever archived.
            $table->foreignId('original_payslip_id')->nullable()->constrained('payslips')->nullOnDelete();

            // Snapshot of what is being corrected, so the adjustment reads
            // correctly without joining a locked record (BR-608).
            $table->string('original_period', 7);
            $table->string('employee_name');
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            $table->string('reason');

            // Signed minor units, effect on net pay: positive pays more,
            // negative claws back (ADR-20).
            $table->bigInteger('amount');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'payroll_run_id'], 'payroll_run_adjustments_tenant_run_index');
            $table->index(['tenant_id', 'employee_id'], 'payroll_run_adjustments_tenant_employee_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_adjustments');
    }
};
