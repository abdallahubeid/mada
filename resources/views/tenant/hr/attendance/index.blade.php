@php
    use App\Domain\Tenancy\Enums\AttendanceStatus;

    $statusClasses = [
        AttendanceStatus::Present->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        AttendanceStatus::Late->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        AttendanceStatus::Absent->value => 'bg-danger-solid/10 text-danger-solid',
        AttendanceStatus::HalfDay->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
    ];
@endphp

<x-layouts.app title="سجل الحضور والغياب">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">سجل الحضور والغياب</h1>
                <p class="mt-1 text-sm text-mist-500">مركز الحضور اليومي مع تسجيل سريع للحضور والانصراف.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('hr.attendance.index') }}" class="grid gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:grid-cols-4 dark:border-ink-600 dark:bg-ink-800">
            <div>
                <label for="date" class="mb-1.5 block text-xs font-medium text-mist-500">التاريخ</label>
                <input id="date" type="date" name="date" dir="ltr" value="{{ $filters['date'] }}" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
            </div>
            <div>
                <label for="employee_id" class="mb-1.5 block text-xs font-medium text-mist-500">الموظف</label>
                <select id="employee_id" name="employee_id" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['employee_id'] === 'all')>الكل</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($filters['employee_id'] === (string) $employee->id)>{{ $employee->full_name }}</option>
                    @endforeach
                </select>
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
            <div class="flex items-end">
                <button type="submit" class="rounded-xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white dark:bg-ink-50 dark:text-ink-900">تصفية</button>
            </div>
        </form>

        @can('hr.attendance.create')
            <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">تسجيل سريع</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($employees->take(12) as $employee)
                        @php $today = $todayByEmployee->get($employee->id); @endphp
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-mist-100 px-3 py-2 dark:border-ink-700">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-ink-800 dark:text-ink-100">{{ $employee->full_name }}</p>
                                <p class="text-xs text-mist-500">
                                    @if ($today?->check_in)
                                        حضور {{ $today->check_in->format('H:i') }}
                                        @if ($today->check_out)
                                            · انصراف {{ $today->check_out->format('H:i') }}
                                        @endif
                                    @else
                                        لم يُسجَّل بعد
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0">
                                @if ($today?->check_in === null)
                                    <form method="POST" action="{{ route('hr.attendance.check-in') }}">
                                        @csrf
                                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                        <button type="submit" class="rounded-lg bg-brand-500 px-2.5 py-1 text-xs font-semibold text-white">حضور</button>
                                    </form>
                                @elseif ($today->check_out === null)
                                    <form method="POST" action="{{ route('hr.attendance.check-out') }}">
                                        @csrf
                                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                        <button type="submit" class="rounded-lg bg-amber-400 px-2.5 py-1 text-xs font-semibold text-amber-950">انصراف</button>
                                    </form>
                                @else
                                    <span class="text-xs font-semibold text-mist-400">مكتمل</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endcan

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الموظف</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">القسم</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الحضور</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الانصراف</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الساعات</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($logs as $log)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 text-start">
                                <a href="{{ route('hr.employees.show', ['employee' => $log->employee, 'tab' => 'attendance']) }}" class="font-medium text-ink-900 hover:text-brand-600 dark:text-ink-50">
                                    {{ $log->employee?->full_name ?? '—' }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-mist-500 text-start">{{ $log->employee?->department?->name ?? '—' }}</td>
                            <td class="px-3 py-2 tabular-nums text-mist-600 text-start"><x-ui.ltr>{{ $log->check_in?->format('H:i') ?? '—' }}</x-ui.ltr></td>
                            <td class="px-3 py-2 tabular-nums text-mist-600 text-start"><x-ui.ltr>{{ $log->check_out?->format('H:i') ?? '—' }}</x-ui.ltr></td>
                            <td class="px-3 py-2 tabular-nums text-mist-600 text-start"><x-ui.ltr>{{ $log->workedHoursLabel() }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-center">
                                <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$log->status->value] ?? ''])>
                                    {{ $log->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="7" icon="document" message="لا توجد سجلات لهذا التاريخ." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
