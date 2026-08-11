<?php

use App\Domain\Tenancy\ApprovableCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalize audit_logs.subject_type from fully-qualified class names to
 * morph-map aliases (BR-902).
 *
 * TenantAuditor writes `$subject?->getMorphClass()`. Registering the morph map
 * changed that return value for mapped models, so rows written before the map
 * hold an FQCN while rows written after hold the alias. Both still resolve
 * through AuditLog::subject() — Laravel falls back to treating an unmapped
 * string as a class name — but leaving the split in place means any future
 * subject_type filter silently misses half the history.
 *
 * Driven off the catalog rather than a hardcoded class, so it stays correct as
 * PAYROLL_RUN, EXPENSE and OFFBOARDING_SETTLEMENT join the map in Phase 2A.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (ApprovableCatalog::morphMap() as $alias => $class) {
            DB::table('audit_logs')
                ->where('subject_type', $class)
                ->update(['subject_type' => $alias]);
        }
    }

    public function down(): void
    {
        foreach (ApprovableCatalog::morphMap() as $alias => $class) {
            DB::table('audit_logs')
                ->where('subject_type', $alias)
                ->update(['subject_type' => $class]);
        }
    }
};
