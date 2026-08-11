<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suspension audit columns for the tenant lifecycle (ADR-04).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THESE ARE SEPARATE FROM THE REVIEW COLUMNS
 *
 * `reviewed_at`/`reviewed_by`/`rejection_reason` record the ONE-TIME decision
 * on a registration: an application is approved or refused once, and the row
 * never returns to `pending_approval`.
 *
 * Suspension is repeatable — a tenant can be suspended, reactivated, and
 * suspended again. Reusing the review columns would mean the second suspension
 * overwrote the record of the original approval, so "who let this customer in"
 * would be destroyed by an unrelated billing action months later.
 *
 * These three columns therefore describe only the CURRENT suspension and are
 * cleared on reactivation. The durable history of every transition lives in
 * `platform_audit_logs`, which is append-only — that is the record you read to
 * answer "how many times has this tenant been suspended", not these columns.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('rejection_reason');
            $table->text('suspension_reason')->nullable()->after('suspended_at');

            // nullOnDelete, matching `reviewed_by`: removing a Super Admin
            // account must never cascade into deleting customer tenants.
            $table->foreignId('suspended_by')->nullable()->after('suspension_reason')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropColumn(['suspended_at', 'suspension_reason']);
        });
    }
};
