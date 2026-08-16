<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give an employee a contact email of their own.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS COLUMN HAS TO EXIST
 *
 * Email previously lived ONLY on `users`. That is fine for staff who log in,
 * but the whole premise of the non-account employee screen is people who have
 * no `users` row at all — so there was nowhere to record how to reach them,
 * and "create an account for these ten employees" had no address to send an
 * invite to.
 *
 * NULLABLE on purpose. A cleaner, non-null tenant-scoped unique column would
 * force a value onto every historical employee row at migration time, and the
 * honest value for most of them is "we don't know". Bulk account creation
 * skips employees without an email rather than inventing one — a fabricated
 * address bounces, and a bounced invite is worse than a visible skip.
 *
 * The unique index is COMPOSITE with tenant_id, not global: two different
 * tenants may legitimately employ the same person, and a global unique index
 * would let one tenant's data leak the existence of another's.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('last_name');

            // Partial uniqueness is not portable, so NULLs are simply allowed
            // to repeat — MySQL treats NULL as distinct in a unique index,
            // which is exactly the behaviour wanted here.
            $table->unique(['tenant_id', 'email'], 'employees_tenant_email_unique');
        });

        /*
         * Backfill from the linked user where one exists, so employees who
         * already have logins arrive with their real address rather than a
         * blank that HR would have to retype.
         */
        DB::statement('
            UPDATE employees e
            INNER JOIN users u ON u.id = e.user_id
            SET e.email = u.email
            WHERE e.user_id IS NOT NULL
              AND e.email IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique('employees_tenant_email_unique');
            $table->dropColumn('email');
        });
    }
};
