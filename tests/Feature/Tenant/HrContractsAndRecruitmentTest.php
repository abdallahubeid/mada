<?php

use App\Domain\Tenancy\Enums\ApplicationStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\Enums\EmploymentType;
use App\Domain\Tenancy\Enums\JobPostingStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantPortalSetting;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('owner can create a contract and see expiration alerts within 30 days', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Lina',
        'last_name' => 'Hassan',
    ]);

    $this->post(route('hr.contracts.store'), [
        'employee_id' => $employee->id,
        'contract_type' => ContractType::FullTime->value,
        'start_date' => now()->subMonths(6)->toDateString(),
        'end_date' => now()->addDays(15)->toDateString(),
        'probation_end_date' => null,
        'status' => ContractStatus::Active->value,
        'notes' => 'Renewal pending',
    ])->assertRedirect(route('hr.contracts.index'));

    $contract = EmployeeContract::query()->first();

    expect($contract)->not->toBeNull()
        ->and($contract->employee_id)->toBe($employee->id)
        ->and($contract->isExpiringSoon())->toBeTrue();

    $this->get(route('hr.contracts.index'))
        ->assertOk()
        ->assertSee('عقود تنتهي خلال 30 يوماً')
        ->assertSee('Lina Hassan');

    $this->get(route('hr.contracts.index', ['expiring' => 1]))
        ->assertOk()
        ->assertSee('Lina Hassan');
});

test('owner can publish a job posting and guests can apply publicly', function () {
    Storage::fake('custom');

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'slug' => 'acme-hire',
        'name' => 'Acme Hire',
    ]);

    TenantPortalSetting::query()->create(TenantPortalSetting::defaultAttributes($user->tenant));

    $this->post(route('hr.jobs.store'), [
        'title' => 'Backend Engineer',
        'department_id' => null,
        'employment_type' => EmploymentType::FullTime->value,
        'location' => 'Riyadh',
        'salary_range' => '15k-20k',
        'description' => 'Build Laravel APIs.',
        'requirements' => "PHP\nLaravel\nMySQL",
        'status' => JobPostingStatus::Draft->value,
    ])->assertRedirect(route('hr.jobs.index'));

    $job = JobPosting::query()->where('title', 'Backend Engineer')->first();

    expect($job)->not->toBeNull()
        ->and($job->status)->toBe(JobPostingStatus::Draft)
        ->and($job->slug)->not->toBeEmpty();

    $this->patch(route('hr.jobs.status', $job), [
        'status' => JobPostingStatus::Published->value,
    ])->assertRedirect();

    expect($job->fresh()->status)->toBe(JobPostingStatus::Published);

    $this->post(route('hr.jobs.store'), [
        'title' => 'Hidden Draft',
        'employment_type' => EmploymentType::PartTime->value,
        'description' => 'Should not appear publicly.',
        'status' => JobPostingStatus::Draft->value,
    ]);

    auth()->logout();

    $this->get(route('portal.careers', 'acme-hire'))
        ->assertOk()
        ->assertSee('Backend Engineer')
        ->assertDontSee('Hidden Draft');

    $this->get(route('portal.jobs.show', ['acme-hire', $job->slug]))
        ->assertOk()
        ->assertSee('Backend Engineer');

    $cv = UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf');

    $this->post(route('portal.jobs.apply', ['acme-hire', $job->slug]), [
        'applicant_name' => 'Khalid Omar',
        'email' => 'khalid@example.test',
        'phone' => '+966500111222',
        'cover_letter' => 'Excited to join.',
        'cv' => $cv,
    ])->assertRedirect(route('portal.jobs.show', ['acme-hire', $job->slug]));

    $application = JobApplication::query()->first();

    expect($application)->not->toBeNull()
        ->and($application->job_posting_id)->toBe($job->id)
        ->and($application->applicant_name)->toBe('Khalid Omar')
        ->and($application->status)->toBe(ApplicationStatus::New)
        ->and($application->cv_path)->not->toBeNull();
});

test('hr can update ats stages and convert accepted applicant to employee', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $job = JobPosting::factory()->published()->create([
        'tenant_id' => $user->tenant_id,
        'title' => 'Product Designer',
    ]);

    $application = JobApplication::factory()->create([
        'tenant_id' => $user->tenant_id,
        'job_posting_id' => $job->id,
        'applicant_name' => 'Nada Saleh',
        'email' => 'nada@example.test',
        'phone' => '+966500333444',
        'cv_path' => 'tenant/'.$user->tenant_id.'/applications/cvs/nada.pdf',
        'status' => ApplicationStatus::New,
    ]);

    $this->put(route('hr.applications.update', $application), [
        'status' => ApplicationStatus::UnderReview->value,
    ])->assertRedirect(route('hr.applications.show', $application));

    expect($application->fresh()->status)->toBe(ApplicationStatus::UnderReview);

    $this->put(route('hr.applications.update', $application), [
        'status' => ApplicationStatus::Interviewed->value,
    ]);

    $this->put(route('hr.applications.update', $application), [
        'status' => ApplicationStatus::Accepted->value,
    ]);

    expect($application->fresh()->status)->toBe(ApplicationStatus::Accepted);

    $this->post(route('hr.applications.convert', $application))
        ->assertRedirect();

    $application->refresh();
    $employee = Employee::query()->find($application->converted_employee_id);

    expect($employee)->not->toBeNull()
        ->and($employee->first_name)->toBe('Nada')
        ->and($employee->last_name)->toBe('Saleh')
        ->and($employee->phone)->toBe('+966500333444')
        ->and($employee->cv_path)->toBe($application->cv_path)
        ->and($employee->job_title)->toBe('Product Designer');

    $this->post(route('hr.applications.convert', $application))
        ->assertRedirect(route('hr.applications.show', $application));

    expect(Employee::query()->count())->toBe(1);
});

test('draft jobs are not publicly accessible and applications are tenant isolated', function () {
    $tenantA = Tenant::factory()->active()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->active()->create(['slug' => 'tenant-b']);

    app(TenantContext::class)->setTenant($tenantA);
    TenantPortalSetting::query()->create(TenantPortalSetting::defaultAttributes($tenantA));
    $draft = JobPosting::factory()->create([
        'tenant_id' => $tenantA->id,
        'title' => 'Secret Draft',
        'slug' => 'secret-draft',
        'status' => JobPostingStatus::Draft,
    ]);
    $published = JobPosting::factory()->published()->create([
        'tenant_id' => $tenantA->id,
        'title' => 'Open Role',
        'slug' => 'open-role',
    ]);
    app(TenantContext::class)->setTenant(null);

    app(TenantContext::class)->setTenant($tenantB);
    TenantPortalSetting::query()->create(TenantPortalSetting::defaultAttributes($tenantB));
    JobPosting::factory()->published()->create([
        'tenant_id' => $tenantB->id,
        'title' => 'Other Tenant Role',
        'slug' => 'other-role',
    ]);
    app(TenantContext::class)->setTenant(null);

    $this->get(route('portal.jobs.show', ['tenant-a', $draft->slug]))->assertNotFound();
    $this->get(route('portal.jobs.show', ['tenant-a', $published->slug]))->assertOk()->assertSee('Open Role');
    $this->get(route('portal.careers', 'tenant-a'))->assertOk()->assertDontSee('Other Tenant Role');
});
