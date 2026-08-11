<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidate interview stages for an ATS application (Phase 3 ATS extension).
 *
 * ONE-TO-MANY on purpose: a candidate routinely goes through a screening call,
 * a technical round and a panel. Hanging `scheduled_at` off `job_applications`
 * would model only the most recent stage and silently overwrite the history of
 * every earlier one.
 *
 * The email actually sent is NOT stored here — it is composed per-send by the
 * HR Manager and recorded in the audit log by `ScheduleInterviewAction`. Adding
 * a copy of the body to this table would duplicate an audited fact and invite
 * the two to drift.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();

            // The interviewer is a USER, not an employee: they receive an in-app
            // notification, and notifications are addressed to user accounts.
            $table->foreignId('interviewer_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('scheduled_at');

            /*
             * One field for both a physical address and a manual meeting URL.
             * Splitting them would force a "kind" discriminator that nothing
             * currently branches on — the candidate reads whichever it is.
             */
            $table->string('location_or_link')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // tenant_id leads every composite index (NFR-06).
            $table->index(['tenant_id', 'scheduled_at'], 'interviews_tenant_scheduled_index');
            $table->index(['tenant_id', 'job_application_id'], 'interviews_tenant_application_index');
            $table->index(['tenant_id', 'interviewer_id'], 'interviews_tenant_interviewer_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
