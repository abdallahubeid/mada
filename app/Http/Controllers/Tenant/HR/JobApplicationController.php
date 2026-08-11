<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\ApplicationStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Support\InterviewMessageTemplate;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\ApplicantAccepted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateJobApplicationRequest;
use App\Models\User;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobApplicationController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
    ) {}

    public function index(Request $request): View
    {
        $applications = JobApplication::query()
            ->with(['jobPosting.department'])
            ->when(
                $request->filled('job_posting_id') && $request->string('job_posting_id') !== 'all',
                fn ($query) => $query->where('job_posting_id', (int) $request->integer('job_posting_id')),
            )
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->latest()
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.hr.applications.index', [
            'applications' => $applications,
            'jobs' => JobPosting::query()->orderBy('title')->pluck('title', 'id'),
            'statuses' => ApplicationStatus::cases(),
            'filters' => [
                'job_posting_id' => (string) $request->string('job_posting_id', 'all'),
                'status' => (string) $request->string('status', 'all'),
            ],
        ]);
    }

    public function show(JobApplication $application): View
    {
        $this->ensureTenantApplication($application);

        return view('tenant.hr.applications.show', [
            'application' => $application->load([
                'jobPosting.department',
                'convertedEmployee',
                'interviews.interviewer',
            ]),
            'statuses' => ApplicationStatus::cases(),

            /*
             * Interviewers are USERS, not employees — the interview notification
             * is delivered to a user account. `User` carries no global tenant
             * scope, so the tenant filter here is explicit and load-bearing.
             */
            'interviewers' => User::query()
                ->where('tenant_id', $this->tenantContext->getTenantId())
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id'),

            'interviewTags' => InterviewMessageTemplate::tags(),
            'defaultSubject' => InterviewMessageTemplate::defaultSubject(),
            'defaultBody' => InterviewMessageTemplate::defaultBody(),
        ]);
    }

    public function update(UpdateJobApplicationRequest $request, JobApplication $application): RedirectResponse
    {
        $this->ensureTenantApplication($application);

        $application->update($request->validated());

        flash()->info('تم تحديث حالة الطلب بنجاح.');

        return redirect()->route('hr.applications.show', $application);
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        $this->ensureTenantApplication($application);

        $application->delete();

        $this->trash->flashSoftDeleted('تم حذف طلب التقديم بنجاح.', 'job-applications', $application);

        return redirect()->route('hr.applications.index');
    }

    public function convertToEmployee(JobApplication $application): RedirectResponse
    {
        $this->ensureTenantApplication($application);

        if ($application->isConverted()) {
            flash()->error('تم تحويل هذا المتقدم إلى موظف مسبقاً.');

            return redirect()->route('hr.applications.show', $application);
        }

        if ($application->status !== ApplicationStatus::Accepted) {
            flash()->error('يجب قبول الطلب قبل تحويله إلى موظف.');

            return redirect()->route('hr.applications.show', $application);
        }

        $employee = DB::transaction(function () use ($application): Employee {
            $application->loadMissing('jobPosting');

            $nameParts = preg_split('/\s+/u', trim($application->applicant_name), 2) ?: [];
            $firstName = $nameParts[0] ?? $application->applicant_name;
            $lastName = $nameParts[1] ?? '—';

            $employee = Employee::query()->create([
                'department_id' => $application->jobPosting?->department_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $application->phone,
                'cv_path' => $application->cv_path,
                'job_title' => $application->jobPosting?->title ?: 'موظف جديد',
                'joining_date' => now()->toDateString(),
                'status' => EmployeeStatus::Active,
            ]);

            $application->update([
                'converted_employee_id' => $employee->id,
                'status' => ApplicationStatus::Accepted,
            ]);

            return $employee;
        });

        event(new ApplicantAccepted($application->fresh(), $employee));

        flash()->success('تم إنشاء ملف الموظف من طلب التقديم.');

        return redirect()->route('hr.employees.show', $employee);
    }

    private function ensureTenantApplication(JobApplication $application): void
    {
        abort_unless(
            (int) $application->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
