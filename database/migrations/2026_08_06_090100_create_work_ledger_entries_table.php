<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materialized Work Ledger (ADR-21, MODULES.md §4.2).
 *
 * One row per employee per date — the sole source of absence deductions
 * (BR-602/BR-404). A derived projection, fully reconstructible from
 * Work Calendar + Attendance + approved Leave.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            // Resolved classification, mutually exclusive. Precedence:
            // holiday > weekend > excused > present > absent (BR-403).
            $table->string('day_type', 16);

            // Which reconciliation input produced the classification.
            $table->string('source', 32);

            // Provenance — lets an auditor trace a deduction back to its evidence.
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('leave_request_id')->nullable()->constrained()->nullOnDelete();

            // Integer minutes, never float hours: this multiplies into money on
            // hourly contracts, where float drift is unrecoverable (ADR-20).
            $table->unsignedInteger('worked_minutes')->nullable();

            $table->timestamps();

            /*
             * No softDeletes, and no created_by/updated_by: this is a derived
             * projection written by a service, rebuilt by hard-delete + reinsert.
             * The single documented exception to NFR-10 — see ADR-21 and
             * DATABASE_ROADMAP.md §1.8.
             */

            $table->unique(['tenant_id', 'employee_id', 'date'], 'work_ledger_entries_employee_date_unique');
            $table->index(['tenant_id', 'date', 'day_type'], 'work_ledger_entries_tenant_date_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_ledger_entries');
    }
};
