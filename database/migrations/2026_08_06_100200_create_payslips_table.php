<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One payslip per employee per payroll run.
 *
 * BR-608: everything below the "frozen snapshot" marker is copied at lock time
 * so a locked payslip renders from its own columns alone — no join to
 * employees, employee_contracts or work_ledger_entries. Editing a contract in
 * September must never change what August's payslip shows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_contract_id')->nullable()->constrained()->nullOnDelete();

            // ---- Frozen snapshot (BR-608) ----
            $table->string('employee_name');
            $table->string('job_title')->nullable();
            $table->string('department_name')->nullable();
            $table->string('pay_basis', 16);
            $table->unsignedBigInteger('base_rate');
            $table->string('pay_currency', 3);

            // Reconciled Work Ledger counts for the period (BR-602).
            $table->unsignedSmallInteger('period_scheduled_days')->default(0);
            $table->unsignedSmallInteger('scheduled_days')->default(0);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('excused_days')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->unsignedInteger('worked_minutes')->default(0);

            /*
             * ---- Computed money, signed minor units (ADR-20) ----
             *
             * Every amount is expressed as its EFFECT ON NET PAY, so one sign
             * rule covers the whole table: positive adds, negative subtracts.
             *   net = base_amount + absence_deduction + allowances_total + deductions_total
             * absence_deduction and deductions_total are therefore <= 0.
             */
            $table->bigInteger('base_amount')->default(0);
            $table->bigInteger('absence_deduction')->default(0);
            $table->bigInteger('allowances_total')->default(0);
            $table->bigInteger('deductions_total')->default(0);
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'payroll_run_id', 'employee_id'], 'payslips_run_employee_unique');
            $table->index(['tenant_id', 'employee_id', 'payroll_run_id'], 'payslips_tenant_employee_run_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
