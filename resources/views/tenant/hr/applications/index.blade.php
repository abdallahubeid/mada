@php
    use App\Domain\Tenancy\Enums\ApplicationStatus;

    $statusClasses = [
        ApplicationStatus::New->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
        ApplicationStatus::UnderReview->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        ApplicationStatus::Interviewed->value => 'bg-violet-400/15 text-violet-800 dark:text-violet-300',
        ApplicationStatus::Accepted->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        ApplicationStatus::Rejected->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="طلبات التقديم">
    <div class="space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">طلبات التقديم</h1>
            <p class="mt-1 text-sm text-mist-500">لوحة ATS لمتابعة مراحل المرشحين.</p>
        </div>

        <form method="GET" action="{{ route('hr.applications.index') }}" class="grid gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:grid-cols-3 dark:border-ink-600 dark:bg-ink-800">
            <div>
                <label for="job_posting_id" class="mb-1.5 block text-xs font-medium text-mist-500">الوظيفة</label>
                <select id="job_posting_id" name="job_posting_id" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['job_posting_id'] === 'all')>الكل</option>
                    @foreach ($jobs as $id => $title)
                        <option value="{{ $id }}" @selected($filters['job_posting_id'] === (string) $id)>{{ $title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-xs font-medium text-mist-500">مرحلة ATS</label>
                <select id="status" name="status" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['status'] === 'all')>الكل</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white dark:bg-ink-50 dark:text-ink-900">تصفية</button>
            </div>
        </form>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">المتقدم</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الوظيفة</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">البريد</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">المرحلة</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($applications as $application)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-medium text-ink-900 dark:text-ink-50 text-start">{{ $application->applicant_name }}</td>
                            <td class="px-3 py-2 text-mist-500 text-start">{{ $application->jobPosting?->title ?? '—' }}</td>
                            <td class="px-3 py-2 text-mist-500 text-start"><x-ui.ltr>{{ $application->email }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-start">
                                <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$application->status->value] ?? ''])>
                                    {{ $application->status->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex justify-center gap-2">
                                    @can('hr.applications.view')
                                        <a href="{{ route('hr.applications.show', $application) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold hover:border-brand-500 dark:border-ink-600">عرض</a>
                                    @endcan
                                    @can('hr.applications.delete')
                                        <form method="POST" action="{{ route('hr.applications.destroy', $application) }}" data-swal-confirm data-swal-title="حذف هذا الطلب؟">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="6" icon="inbox" message="لا توجد طلبات تقديم بعد." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $applications->links() }}</div>
    </div>
</x-layouts.app>
