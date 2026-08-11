@php
    use App\Domain\Tenancy\Enums\EmployeeStatus;

    $statusClasses = [
        EmployeeStatus::Active->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        EmployeeStatus::OnLeave->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        EmployeeStatus::Resigned->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        EmployeeStatus::Suspended->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="الموظفون">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الموظفون</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">ملفات الموظفين والهيكل الوظيفي (بدون بيانات الرواتب).</p>
            </div>
            @can('hr.employees.create')
                <a
                    href="{{ route('hr.employees.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300"
                >
                    إضافة موظف
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('hr.employees.index') }}" class="grid gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:grid-cols-4 dark:border-ink-600 dark:bg-ink-800">
            <div class="sm:col-span-2">
                <label for="q" class="mb-1.5 block text-xs font-medium text-mist-500">بحث</label>
                <input
                    id="q"
                    type="search"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="الاسم أو المسمى الوظيفي"
                    class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                >
            </div>
            <div>
                <label for="department_id" class="mb-1.5 block text-xs font-medium text-mist-500">القسم</label>
                <select id="department_id" name="department_id" class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['department_id'] === 'all')>الكل</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" @selected($filters['department_id'] === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-xs font-medium text-mist-500">الحالة</label>
                <select id="status" name="status" class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['status'] === 'all')>الكل</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 sm:col-span-4">
                <button type="submit" class="rounded-xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-ink-800 dark:bg-ink-50 dark:text-ink-900">تصفية</button>
                <a href="{{ route('hr.employees.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-500 hover:text-ink-700 dark:hover:text-mist-200">إعادة ضبط</a>
            </div>
        </form>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الاسم</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">المسمى الوظيفي</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">القسم</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">تاريخ الالتحاق</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($employees as $employee)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-start">
                                <div class="flex items-center gap-3">
                                    <img
                                        src="{{ $employee->avatarUrl() }}"
                                        alt=""
                                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-mist-200 dark:ring-ink-600"
                                    >
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-ink-900 dark:text-ink-50">{{ $employee->full_name }}</p>
                                        @if ($employee->phone)
                                            <p class="truncate text-xs text-mist-500" dir="ltr">{{ $employee->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-mist-600 dark:text-mist-300 text-start">{{ $employee->job_title }}</td>
                            <td class="px-4 py-3 text-mist-500 text-start">{{ $employee->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    $statusClasses[$employee->status->value] ?? 'bg-mist-100 text-mist-600',
                                ])>
                                    {{ $employee->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-mist-500 text-start"><x-ui.ltr>{{ $employee->joining_date?->format('Y-m-d') ?? '—' }}</x-ui.ltr></td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-end gap-2">
                                    @can('hr.employees.view')
                                        <a
                                            href="{{ route('hr.employees.show', $employee) }}"
                                            class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600 dark:hover:border-emerald-400"
                                        >
                                            عرض
                                        </a>
                                    @endcan
                                    @can('hr.employees.update')
                                        <a
                                            href="{{ route('hr.employees.edit', $employee) }}"
                                            class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600 dark:hover:border-emerald-400"
                                        >
                                            تعديل
                                        </a>
                                    @endcan
                                    @can('hr.employees.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('hr.employees.destroy', $employee) }}"
                                            data-swal-confirm
                                            data-swal-title="حذف ملف هذا الموظف؟"
                                            data-swal-text="سيتم الحذف الناعم ويمكن استعادته لاحقاً."
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid transition hover:bg-danger-solid/10 dark:border-ink-600">
                                                حذف
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="7" icon="👥" message="لا يوجد موظفون بعد." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $employees->links() }}
        </div>
    </div>
</x-layouts.app>
