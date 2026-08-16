<?php

use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Regression cover for the CV disclosure.
 *
 * CVs used to be written to the `custom` disk, whose root IS the web document
 * root, so every résumé was downloadable by anyone holding the URL — no
 * session, no tenant check, no permission. These tests pin each half of the
 * fix: the file is not reachable directly, and the route that replaces it
 * refuses everyone who should not see it.
 */
function makeEmployeeWithCv(int $tenantId): Employee
{
    Storage::disk('private')->put(
        $path = "tenant/{$tenantId}/employees/cvs/secret-resume.pdf",
        'CONFIDENTIAL RESUME BODY'
    );

    return Employee::factory()->create([
        'tenant_id' => $tenantId,
        'cv_path' => $path,
    ]);
}

test('the private disk is not the web root', function () {
    /*
     * The root assertion. If this ever points back inside public/ the whole
     * protection collapses no matter what the routes do.
     */
    $private = config('filesystems.disks.private.root');

    expect($private)->toBe(storage_path('app/private'))
        ->and(str_contains($private, 'public'))->toBeFalse()
        ->and(config('filesystems.disks.private.url' ?? ''))->toBeNull();
});

test('a guest cannot download a cv', function () {
    // A real tenant row — `employees.tenant_id` is a FK, so an invented id
    // fails on insert rather than exercising the thing under test.
    $tenant = \App\Domain\Tenancy\Models\Tenant::factory()->create();

    $employee = makeEmployeeWithCv($tenant->id);

    $this->get(route('hr.employees.cv', $employee))
        ->assertRedirect(route('login'));
});

test('an authenticated employee without hr permission is refused', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $employee = makeEmployeeWithCv($user->tenant_id);

    $this->get(route('hr.employees.cv', $employee))
        ->assertForbidden();
});

test('an hr manager can download a cv from their own tenant', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $employee = makeEmployeeWithCv($hr->tenant_id);

    $response = $this->get(route('hr.employees.cv', $employee));

    $response->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

test('an hr manager cannot download another tenant s cv', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    // Same permission, different tenant — the case a permission check alone
    // does NOT catch, and the reason the controller scopes by tenant as well.
    $otherTenant = \App\Domain\Tenancy\Models\Tenant::factory()->create();
    $foreign = makeEmployeeWithCv($otherTenant->id);

    $this->get(route('hr.employees.cv', $foreign))
        ->assertNotFound();
});

test('a stale cv_path 404s instead of erroring', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $hr->tenant_id,
        'cv_path' => 'tenant/'.$hr->tenant_id.'/employees/cvs/deleted-file.pdf',
    ]);

    $this->get(route('hr.employees.cv', $employee))
        ->assertNotFound();
});

test('an employee with no cv 404s', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $hr->tenant_id,
        'cv_path' => null,
    ]);

    $this->get(route('hr.employees.cv', $employee))
        ->assertNotFound();
});

test('no cv files remain under the public web root', function () {
    /*
     * Guards the migration, not the code path: a future upload helper that
     * quietly writes to the `custom` disk again would reintroduce the exact
     * vulnerability while every route test above still passed.
     */
    $leaked = [];

    foreach ([public_path('tenant')] as $dir) {
        if (! is_dir($dir)) {
            continue;
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if ($file->isFile() && str_contains(str_replace('\\', '/', $file->getPathname()), '/cvs/')) {
                $leaked[] = $file->getFilename();
            }
        }
    }

    expect($leaked)->toBe([]);
});
