<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Authorised streaming for confidential employee documents (CVs).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY A CONTROLLER AND NOT A LINK
 *
 * CVs used to be written to the `custom` disk, whose root IS the web document
 * root. That made every résumé readable by anyone holding the URL — no
 * session, no tenant check, no permission. Anyone who had ever been sent a
 * link (or could guess a filename) kept access forever, including former
 * staff and rejected candidates.
 *
 * Files now live on the `private` disk, which no vhost serves. This action is
 * the only way to read one, and it runs three checks in order:
 *
 *   1. `auth` + `permission:hr.employees.view` — route middleware.
 *   2. Tenant ownership — an HR manager at tenant A must not read tenant B's
 *      files even though they hold the same permission name. This is the
 *      check a permission system alone does NOT give you.
 *   3. File existence — a stale `cv_path` 404s rather than 500s.
 *
 * The response streams rather than reading into memory: CVs are small, but an
 * endpoint that loads whole files into PHP memory is a denial-of-service lever
 * once someone loops it.
 * ─────────────────────────────────────────────────────────────────────────
 */
class EmployeeDocumentController extends Controller
{
    public function __construct(private TenantContext $tenantContext) {}

    public function cv(Employee $employee): StreamedResponse
    {
        /*
         * Tenant scoping BEFORE anything else, and as a 404 rather than a 403.
         * A 403 would confirm that the employee id exists in some other
         * tenant; 404 keeps cross-tenant existence unobservable.
         */
        abort_unless(
            (int) $employee->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );

        abort_if(blank($employee->cv_path), 404);

        $disk = Storage::disk('private');

        abort_unless($disk->exists($employee->cv_path), 404);

        /*
         * A human filename, not the random storage hash. `Str::ascii` is not
         * applied — the employee's name may be Arabic, and the RFC 6266
         * `filename*` parameter that Symfony emits handles UTF-8 correctly.
         */
        $extension = pathinfo($employee->cv_path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = trim($employee->full_name).'-CV.'.$extension;

        return $disk->download($employee->cv_path, $filename);
    }
}
