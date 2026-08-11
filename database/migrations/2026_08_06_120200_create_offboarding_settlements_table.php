<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Final settlement on employee termination (BR-606).
 *
 * Every computed figure is SNAPSHOT at generation time, in minor units, for the
 * same reason payslips are (BR-608): a settlement is a permanent financial
 * record and must render identically forever, without joining a contract or a
 * leave balance that may since have changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_contract_id')->nullable()->constrained()->nullOnDelete();

            // ---- Snapshot ----
            $table->string('employee_name');
            $table->string('job_title')->nullable();
            $table->string('department_name')->nullable();
            $table->string('pay_basis', 16);
            $table->unsignedBigInteger('base_rate');
            $table->string('currency', 3);
            $table->date('joining_date')->nullable();
            $table->date('last_working_day');
            $table->unsignedInteger('service_months')->default(0);
            $table->unsignedSmallInteger('unused_leave_days')->default(0);

            $table->string('reason', 32);
            $table->string('status', 32)->default('draft');

            /*
             * ---- Money, signed minor units, effect on the final payout ----
             * total = eosb + leave_payout + prorated_salary + deductions,
             * where deductions are <= 0. Same single sign rule as payslips.
             */
            $table->bigInteger('eosb_amount')->default(0);
            $table->bigInteger('leave_payout_amount')->default(0);
            $table->bigInteger('prorated_salary_amount')->default(0);
            $table->bigInteger('loan_deduction_amount')->default(0);
            $table->bigInteger('other_deduction_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status'], 'offboarding_settlements_tenant_status_index');
            $table->index(['tenant_id', 'employee_id'], 'offboarding_settlements_tenant_employee_index');

            /*
             * One LIVE settlement per employee, using the same stored-generated
             * sentinel as payroll_runs.active_period (BR-611): NULL for
             * cancelled or soft-deleted rows, which MySQL treats as distinct,
             * so dead settlements may coexist while only one live one stands.
             */
            $table->unsignedBigInteger('active_employee_id')
                ->storedAs("if(deleted_at is null and status <> 'cancelled', employee_id, null)");

            $table->unique(['tenant_id', 'active_employee_id'], 'offboarding_settlements_active_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_settlements');
    }
};
