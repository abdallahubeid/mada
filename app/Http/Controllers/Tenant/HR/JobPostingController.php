<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\EmploymentType;
use App\Domain\Tenancy\Enums\JobPostingStatus;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreJobPostingRequest;
use App\Http\Requests\Tenant\UpdateJobPostingRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JobPostingController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
    ) {}

    public function index(Request $request): View
    {
        $jobs = JobPosting::query()
            ->with('department')
            ->withCount('applications')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $q = '%'.mb_strtolower((string) $request->string('q')).'%';
                $query->whereRaw('LOWER(title) like ?', [$q]);
            })
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->latest()
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.hr.jobs.index', [
            'jobs' => $jobs,
            'statuses' => JobPostingStatus::cases(),
            'filters' => [
                'q' => (string) $request->string('q'),
                'status' => (string) $request->string('status', 'all'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('tenant.hr.jobs.create', [
            'job' => new JobPosting([
                'status' => JobPostingStatus::Draft,
                'employment_type' => EmploymentType::FullTime,
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreJobPostingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = JobPosting::uniqueSlugForTitle(
            $data['title'],
            $this->tenantContext->getTenantId(),
        );

        JobPosting::query()->create($data);

        flash()->success('تم إنشاء الوظيفة بنجاح.');

        return redirect()->route('hr.jobs.index');
    }

    public function edit(JobPosting $job): View
    {
        $this->ensureTenantJob($job);

        return view('tenant.hr.jobs.edit', [
            'job' => $job,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $job): RedirectResponse
    {
        $this->ensureTenantJob($job);

        $data = $request->validated();

        if (($data['title'] ?? null) !== $job->title) {
            $data['slug'] = JobPosting::uniqueSlugForTitle(
                $data['title'],
                $this->tenantContext->getTenantId(),
                $job->id,
            );
        }

        $job->update($data);

        flash()->info('تم تحديث الوظيفة بنجاح.');

        return redirect()->route('hr.jobs.index');
    }

    public function destroy(JobPosting $job): RedirectResponse
    {
        $this->ensureTenantJob($job);

        if ($job->applications()->exists()) {
            flash()->error('لا يمكن حذف وظيفة لديها طلبات تقديم. أغلق الوظيفة بدلاً من ذلك.');

            return redirect()->route('hr.jobs.index');
        }

        $job->delete();

        $this->trash->flashSoftDeleted('تم حذف الوظيفة بنجاح.', 'job-postings', $job);

        return redirect()->route('hr.jobs.index');
    }

    public function updateStatus(Request $request, JobPosting $job): RedirectResponse
    {
        $this->ensureTenantJob($job);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', JobPostingStatus::values())],
        ]);

        $job->update(['status' => $validated['status']]);

        flash()->info('تم تحديث حالة الوظيفة.');

        return redirect()->back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'employmentTypes' => EmploymentType::cases(),
            'statuses' => JobPostingStatus::cases(),
        ];
    }

    private function ensureTenantJob(JobPosting $job): void
    {
        abort_unless(
            (int) $job->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
