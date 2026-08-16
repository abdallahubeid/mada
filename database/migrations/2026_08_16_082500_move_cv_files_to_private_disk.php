<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Move already-uploaded CVs off the web root.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE CODE FIX ALONE DOES NOT CLOSE THE HOLE
 *
 * Pointing new uploads at the private disk protects future files. Everything
 * uploaded before that change is still sitting under public/ and is still
 * downloadable by anyone with the URL. Those are exactly the files most likely
 * to have been shared around, so they are the ones that matter.
 *
 * This is written as a migration rather than a one-off command so it runs
 * automatically on every environment — staging and production included —
 * instead of relying on someone remembering to run a script.
 *
 * It is deliberately IDEMPOTENT and non-fatal: a missing source file is
 * skipped rather than thrown, so a partially-migrated environment can re-run
 * it safely and one absent file cannot block a deploy.
 * ─────────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('custom');
        $private = Storage::disk('private');

        $moved = 0;
        $missing = 0;

        DB::table('employees')
            ->whereNotNull('cv_path')
            ->where('cv_path', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($employees) use ($public, $private, &$moved, &$missing): void {
                foreach ($employees as $employee) {
                    $path = $employee->cv_path;

                    // Already migrated (previous run, or uploaded after the fix).
                    if ($private->exists($path)) {
                        continue;
                    }

                    if (! $public->exists($path)) {
                        $missing++;

                        continue;
                    }

                    $private->put($path, $public->get($path));

                    /*
                     * Delete from public only after the private copy is
                     * confirmed on disk. A failed write followed by a delete
                     * would destroy the only copy of someone's CV.
                     */
                    if ($private->exists($path)) {
                        $public->delete($path);
                        $moved++;
                    }
                }
            });

        /*
         * Job-application CVs live under the same public tree and are the MORE
         * sensitive half — a candidate is not even an employee yet, so a leaked
         * application discloses someone who may never have joined.
         *
         * The table is `job_applications`, NOT `applications`. Getting that
         * wrong the first time produced a guard that read as careful and
         * silently migrated nothing — the files stayed exposed while the
         * migration reported success.
         */
        if (Schema::hasTable('job_applications') && Schema::hasColumn('job_applications', 'cv_path')) {
            DB::table('job_applications')
                ->whereNotNull('cv_path')
                ->where('cv_path', '!=', '')
                ->orderBy('id')
                ->chunkById(200, function ($applications) use ($public, $private, &$moved, &$missing): void {
                    foreach ($applications as $application) {
                        $path = $application->cv_path;

                        if ($private->exists($path)) {
                            continue;
                        }

                        if (! $public->exists($path)) {
                            $missing++;

                            continue;
                        }

                        $private->put($path, $public->get($path));

                        if ($private->exists($path)) {
                            $public->delete($path);
                            $moved++;
                        }
                    }
                });
        }

        if (app()->runningInConsole()) {
            echo "  CV files moved to private storage: {$moved}".($missing > 0 ? " (skipped {$missing} missing)" : '').PHP_EOL;
        }
    }

    /**
     * Deliberately NOT reversible.
     *
     * `down()` would mean copying confidential files back into the web root —
     * re-opening the exact vulnerability this migration exists to close. A
     * rollback that recreates a data-exposure bug is not a rollback worth
     * having, so this is a one-way door and says so.
     */
    public function down(): void
    {
        // No-op on purpose. See the note above.
    }
};
