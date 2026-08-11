@php
    use App\Domain\Tenancy\Enums\JobPostingStatus;

    $statusClasses = [
        JobPostingStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        JobPostingStatus::Published->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        JobPostingStatus::Closed->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="الوظائف والتوظيف">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الوظائف والتوظيف</h1>
                <p class="mt-1 text-sm text-mist-500">إدارة الشواغر ونشرها على الموقع العام.</p>
            </div>
            @can('hr.jobs.create')
                <a href="{{ route('hr.jobs.create') }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                    إضافة وظيفة
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('hr.jobs.index') }}" class="grid gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:grid-cols-3 dark:border-ink-600 dark:bg-ink-800">
            <div class="sm:col-span-2">
                <label for="q" class="mb-1.5 block text-xs font-medium text-mist-500">بحث</label>
                <input id="q" type="search" name="q" value="{{ $filters['q'] }}" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-xs font-medium text-mist-500">الحالة</label>
                <select id="status" name="status" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['status'] === 'all')>الكل</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="rounded-xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white dark:bg-ink-50 dark:text-ink-900">تصفية</button>
            </div>
        </form>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">المسمى</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">القسم</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">النوع</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الطلبات</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($jobs as $job)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-medium text-ink-900 dark:text-ink-50 text-start">{{ $job->title }}</td>
                            <td class="px-4 py-3 text-mist-500 text-start">{{ $job->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-mist-500 text-start">{{ $job->employment_type->label() }}</td>
                            <td class="px-4 py-3 text-center">
                                <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$job->status->value] ?? ''])>
                                    {{ $job->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-start">
                                <a href="{{ route('hr.applications.index', ['job_posting_id' => $job->id]) }}" class="inline-flex min-w-8 items-center justify-center rounded-full bg-emerald-400/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                    {{ $job->applications_count }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @can('hr.jobs.update')
                                        <form method="POST" action="{{ route('hr.jobs.status', $job) }}">
                                            @csrf
                                            @method('PATCH')
                                            @if ($job->status !== JobPostingStatus::Published)
                                                <input type="hidden" name="status" value="published">
                                                <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold hover:border-emerald-400 dark:border-ink-600">نشر</button>
                                            @else
                                                <input type="hidden" name="status" value="closed">
                                                <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold hover:border-amber-400 dark:border-ink-600">إغلاق</button>
                                            @endif
                                        </form>
                                        <a href="{{ route('hr.jobs.edit', $job) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold hover:border-emerald-400 dark:border-ink-600">تعديل</a>
                                    @endcan
                                    @can('hr.jobs.delete')
                                        <form method="POST" action="{{ route('hr.jobs.destroy', $job) }}" data-swal-confirm data-swal-title="حذف هذه الوظيفة؟">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="7" icon="📢" message="لا توجد وظائف بعد." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $jobs->links() }}</div>
    </div>
</x-layouts.app>
