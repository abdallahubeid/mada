@php
    use App\Domain\Tenancy\Enums\AttendanceStatus;
    use App\Domain\Tenancy\Enums\EmployeeStatus;

    $statusClasses = [
        EmployeeStatus::Active->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        EmployeeStatus::OnLeave->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        EmployeeStatus::Resigned->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        EmployeeStatus::Suspended->value => 'bg-danger-solid/10 text-danger-solid',
    ];

    $attendanceStatusClasses = [
        AttendanceStatus::Present->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        AttendanceStatus::Late->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        AttendanceStatus::Absent->value => 'bg-danger-solid/10 text-danger-solid',
        AttendanceStatus::HalfDay->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
    ];

    $activeTab = request('tab', 'overview');
@endphp

<x-layouts.app title="ملف الموظف">
    <div class="mx-auto max-w-5xl space-y-6" x-data="{ activeTab: @js($activeTab), leaveOpen: false }">
        <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <img
                        src="{{ $employee->avatarUrl() }}"
                        alt=""
                        class="h-20 w-20 rounded-full object-cover ring-1 ring-mist-200 dark:ring-ink-600"
                    >
                    <div>
                        <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">{{ $employee->full_name }}</h1>
                        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $employee->job_title }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if ($employee->department)
                                <span class="inline-flex rounded-md bg-mist-100 px-2.5 py-0.5 text-xs font-semibold text-mist-700 dark:bg-ink-700 dark:text-mist-200">
                                    {{ $employee->department->name }}
                                </span>
                            @endif
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                $statusClasses[$employee->status->value] ?? 'bg-mist-100 text-mist-600',
                            ])>
                                {{ $employee->status->label() }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-mist-500">
                            المدير المباشر:
                            <span class="font-medium text-ink-700 dark:text-mist-200">{{ $employee->manager?->full_name ?? '—' }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @can('hr.employees.update')
                        <a
                            href="{{ route('hr.employees.edit', $employee) }}"
                            class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600"
                        >
                            تعديل الموظف
                        </a>
                    @endcan

                    @can('hr.attendance.create')
                        @if ($todayAttendance?->check_in === null)
                            <form method="POST" action="{{ route('hr.attendance.check-in') }}">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                <button type="submit" class="rounded-xl border border-brand-500/40 bg-brand-500/10 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-500/20 dark:text-brand-300">
                                    تسجيل حضور
                                </button>
                            </form>
                        @elseif ($todayAttendance->check_out === null)
                            <form method="POST" action="{{ route('hr.attendance.check-out') }}">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                <button type="submit" class="rounded-xl border border-amber-400/40 bg-amber-400/10 px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-400/20 dark:text-amber-300">
                                    تسجيل انصراف
                                </button>
                            </form>
                        @else
                            <span class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-mist-500 dark:border-ink-600">
                                اكتمل حضور اليوم
                            </span>
                        @endif
                    @endcan

                    <a
                        href="{{ route('hr.employees.index') }}"
                        class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-mist-600 transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600 dark:text-mist-300"
                    >
                        رجوع
                    </a>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-2 border-b border-mist-200 pb-2 dark:border-ink-700">
            <button type="button" @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-brand-500/15 text-brand-700 dark:text-brand-300' : 'text-mist-500 hover:bg-mist-100 dark:hover:bg-ink-800'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">نظرة عامة</button>
            <button type="button" @click="activeTab = 'contract'" :class="activeTab === 'contract' ? 'bg-brand-500/15 text-brand-700 dark:text-brand-300' : 'text-mist-500 hover:bg-mist-100 dark:hover:bg-ink-800'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">العقد النشط</button>
            <button type="button" @click="activeTab = 'attendance'" :class="activeTab === 'attendance' ? 'bg-brand-500/15 text-brand-700 dark:text-brand-300' : 'text-mist-500 hover:bg-mist-100 dark:hover:bg-ink-800'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">سجل الحضور</button>
            <button type="button" @click="activeTab = 'leaves'" :class="activeTab === 'leaves' ? 'bg-brand-500/15 text-brand-700 dark:text-brand-300' : 'text-mist-500 hover:bg-mist-100 dark:hover:bg-ink-800'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">الإجازات</button>
            <button type="button" @click="activeTab = 'performance'" :class="activeTab === 'performance' ? 'bg-brand-500/15 text-brand-700 dark:text-brand-300' : 'text-mist-500 hover:bg-mist-100 dark:hover:bg-ink-800'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">التقييمات</button>
            @can('hr.assets.view_any')
                <button type="button" @click="activeTab = 'assets'" :class="activeTab === 'assets' ? 'bg-brand-500/15 text-brand-700 dark:text-brand-300' : 'text-mist-500 hover:bg-mist-100 dark:hover:bg-ink-800'" class="rounded-xl px-4 py-2 text-sm font-semibold transition">العُهد والأصول</button>
            @endcan
        </div>

        <div x-show="activeTab === 'overview'" x-cloak class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <section class="space-y-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">البيانات الشخصية</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-mist-500">رقم الهوية</dt>
                            <dd class="text-ink-800 dark:text-ink-100" dir="ltr">{{ $employee->national_id ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-mist-500">الجوال</dt>
                            <dd class="text-ink-800 dark:text-ink-100" dir="ltr">{{ $employee->phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-mist-500">العنوان</dt>
                            <dd class="mt-1 text-ink-800 dark:text-ink-100">{{ $employee->address ?? '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="space-y-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">بيانات التوظيف</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-mist-500">تاريخ الالتحاق</dt>
                            <dd class="tabular-nums text-ink-800 dark:text-ink-100" dir="ltr">{{ $employee->joining_date?->format('Y-m-d') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-mist-500">المدير المباشر</dt>
                            <dd class="text-ink-800 dark:text-ink-100">{{ $employee->manager?->full_name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-mist-500">حساب النظام</dt>
                            <dd class="text-ink-800 dark:text-ink-100" dir="ltr">{{ $employee->user?->email ?? '—' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">السيرة الذاتية</h2>
                <div class="mt-3 text-sm">
                    @if ($employee->cvUrl())
                        <a href="{{ $employee->cvUrl() }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline dark:text-brand-300">
                            معاينة / تحميل السيرة الذاتية
                        </a>
                    @else
                        <span class="text-mist-500">لا توجد سيرة ذاتية مرفقة.</span>
                    @endif
                </div>
            </section>

            @if ($employee->subordinates->isNotEmpty())
                <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">المرؤوسون</h2>
                    <ul class="mt-3 space-y-2 text-sm text-mist-600 dark:text-mist-300">
                        @foreach ($employee->subordinates as $subordinate)
                            <li>
                                <a href="{{ route('hr.employees.show', $subordinate) }}" class="font-medium text-brand-600 hover:underline dark:text-brand-300">
                                    {{ $subordinate->full_name }}
                                </a>
                                — {{ $subordinate->job_title }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <div x-show="activeTab === 'contract'" x-cloak>
            <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">العقد النشط</h2>
                @if ($activeContract)
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="flex justify-between gap-3 sm:block">
                            <dt class="text-mist-500">نوع العقد</dt>
                            <dd class="font-medium text-ink-800 dark:text-ink-100">{{ $activeContract->contract_type->label() }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 sm:block">
                            <dt class="text-mist-500">الحالة</dt>
                            <dd class="font-medium text-ink-800 dark:text-ink-100">{{ $activeContract->status->label() }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 sm:block">
                            <dt class="text-mist-500">تاريخ البداية</dt>
                            <dd class="tabular-nums text-ink-800 dark:text-ink-100" dir="ltr">{{ $activeContract->start_date?->format('Y-m-d') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 sm:block">
                            <dt class="text-mist-500">تاريخ النهاية</dt>
                            <dd class="tabular-nums text-ink-800 dark:text-ink-100" dir="ltr">{{ $activeContract->end_date?->format('Y-m-d') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 sm:block">
                            <dt class="text-mist-500">نهاية فترة التجربة</dt>
                            <dd class="tabular-nums text-ink-800 dark:text-ink-100" dir="ltr">{{ $activeContract->probation_end_date?->format('Y-m-d') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 sm:block">
                            <dt class="text-mist-500">حالة التجربة</dt>
                            <dd class="font-medium text-ink-800 dark:text-ink-100">
                                @if ($activeContract->probation_end_date === null)
                                    —
                                @elseif ($activeContract->probation_end_date->isFuture())
                                    جارية حتى {{ $activeContract->probation_end_date->format('Y-m-d') }}
                                @else
                                    منتهية
                                @endif
                            </dd>
                        </div>
                        @if ($activeContract->notes)
                            <div class="sm:col-span-2">
                                <dt class="text-mist-500">ملاحظات</dt>
                                <dd class="mt-1 text-ink-800 dark:text-ink-100">{{ $activeContract->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                    @can('hr.contracts.update')
                        <a href="{{ route('hr.contracts.edit', $activeContract) }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300">تعديل العقد</a>
                    @endcan
                @else
                    <p class="mt-3 text-sm text-mist-500">لا يوجد عقد نشط لهذا الموظف.</p>
                    @can('hr.contracts.create')
                        <a href="{{ route('hr.contracts.create', ['employee_id' => $employee->id]) }}" class="mt-3 inline-flex text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300">إضافة عقد</a>
                    @endcan
                @endif
            </section>
        </div>

        <div x-show="activeTab === 'attendance'" x-cloak class="space-y-4">
            <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">التاريخ</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الحضور</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الانصراف</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الساعات</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($attendances as $attendance)
                            <tr>
                                <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 tabular-nums text-mist-600 text-start"><x-ui.ltr>{{ $attendance->date?->format('Y-m-d') }}</x-ui.ltr></td>
                                <td class="px-3 py-2 tabular-nums text-mist-600 text-start"><x-ui.ltr>{{ $attendance->check_in?->format('H:i') ?? '—' }}</x-ui.ltr></td>
                                <td class="px-3 py-2 tabular-nums text-mist-600 text-start"><x-ui.ltr>{{ $attendance->check_out?->format('H:i') ?? '—' }}</x-ui.ltr></td>
                                <td class="px-3 py-2 tabular-nums text-mist-600 text-start"><x-ui.ltr>{{ $attendance->workedHoursLabel() }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-center">
                                    <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $attendanceStatusClasses[$attendance->status->value] ?? ''])>
                                        {{ $attendance->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="6" icon="clock" message="لا توجد سجلات حضور بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $attendances->withQueryString()->appends(['tab' => 'attendance'])->links() }}</div>
        </div>

        <div x-show="activeTab === 'leaves'" x-cloak class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">أرصدة الإجازات ({{ now()->year }})</h2>
                @can('hr.leaves.create')
                    <button type="button" @click="leaveOpen = true" class="rounded-xl bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-glow hover:bg-brand-600">
                        طلب إجازة جديد
                    </button>
                @endcan
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                @forelse ($leaveBalances as $balance)
                    <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                        <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $balance['type']->name }}</p>
                        <p class="mt-2 text-2xl font-bold text-brand-600 dark:text-brand-300">{{ $balance['remaining'] }}</p>
                        <p class="text-xs text-mist-500">متبقي من {{ $balance['annual'] }} · مستخدم {{ $balance['used'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-mist-500 sm:col-span-3">لا توجد أنواع إجازات بعد. أضفها من إدارة الإجازات.</p>
                @endforelse
            </div>

            <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">النوع</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">من</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">إلى</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الأيام</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($leaveRequests as $leaveRequest)
                            <tr>
                                <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 text-start">{{ $leaveRequest->leaveType?->name ?? '—' }}</td>
                                <td class="px-3 py-2 tabular-nums text-start"><x-ui.ltr>{{ $leaveRequest->start_date?->format('Y-m-d') }}</x-ui.ltr></td>
                                <td class="px-3 py-2 tabular-nums text-start"><x-ui.ltr>{{ $leaveRequest->end_date?->format('Y-m-d') }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-start">{{ $leaveRequest->days_count }}</td>
                                <td class="px-3 py-2 text-center">{{ $leaveRequest->status->label() }}</td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="6" icon="sun" message="لا توجد طلبات إجازة." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $leaveRequests->withQueryString()->appends(['tab' => 'leaves'])->links() }}</div>

            <div
                x-show="leaveOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4"
                @keydown.escape.window="leaveOpen = false"
            >
                <div class="w-full max-w-lg rounded-2xl border border-mist-200 bg-white p-4 shadow-xl dark:border-ink-600 dark:bg-ink-800" @click.outside="leaveOpen = false">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-ink-900 dark:text-ink-50">طلب إجازة جديد</h3>
                        <button type="button" @click="leaveOpen = false" class="text-mist-500">إغلاق</button>
                    </div>
                    <form method="POST" action="{{ route('hr.leaves.requests.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        <div>
                            <label class="mb-1 block text-xs text-mist-500">نوع الإجازة</label>
                            <select name="leave_type_id" required class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                                @foreach ($leaveTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->annual_days }} يوم)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs text-mist-500">من</label>
                                <input type="date" name="start_date" required dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-mist-500">إلى</label>
                                <input type="date" name="end_date" required dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-mist-500">السبب</label>
                            <textarea name="reason" rows="2" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white">إرسال الطلب</button>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'performance'" x-cloak class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">سجل التقييمات الدورية</h2>
                @can('hr.evaluations.access')
                    <a href="{{ route('hr.evaluations.index') }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300">لوحة التقييمات</a>
                @endcan
            </div>
            <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الفترة</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">النوع</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">التقييم</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">المقيّم</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($evaluations as $evaluation)
                            <tr>
                                <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 font-medium text-start"><x-ui.ltr>{{ $evaluation->period_key }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-start">{{ $evaluation->period_type->label() }}</td>
                                <td class="px-3 py-2 tabular-nums font-semibold text-start"><x-ui.ltr>{{ $evaluation->rating ?? '—' }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-start">{{ $evaluation->evaluator?->full_name ?? '—' }}</td>
                                <td class="px-3 py-2 text-center">{{ $evaluation->status->label() }}</td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="6" icon="star" message="لا توجد تقييمات بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $evaluations->withQueryString()->appends(['tab' => 'performance'])->links() }}</div>
        </div>

        @can('hr.assets.view_any')
            <div x-show="activeTab === 'assets'" x-cloak class="space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">العُهد والأصول</h2>
                    <a href="{{ route('tenant.assets.employee', $employee) }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300">عرض صفحة العهدة الكاملة</a>
                </div>

                <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">عهدة نشطة</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-max text-sm">
                            <thead>
                                <tr class="border-b border-mist-100 text-xs text-mist-500 dark:border-ink-700">
                                    <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                                    <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الرمز</th>
                                    <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الأصل</th>
                                    <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">التصنيف</th>
                                    <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">تاريخ الإسناد</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                                @forelse ($activeAssetAssignments as $assignment)
                                    <tr>
                                        <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                        <td class="px-3 py-2 font-mono text-xs text-start"><x-ui.ltr>{{ $assignment->asset?->asset_code }}</x-ui.ltr></td>
                                        <td class="px-3 py-2 text-start">{{ $assignment->asset?->name }}</td>
                                        <td class="px-3 py-2 text-start">{{ $assignment->asset?->category->label() }}</td>
                                        <td class="px-3 py-2 text-start"><x-ui.ltr>{{ $assignment->assigned_at?->format('Y-m-d') }}</x-ui.ltr></td>
                                    </tr>
                                @empty
                                    <x-ui.table-empty :colspan="5" icon="archive" message="لا توجد أصول مسندة حالياً." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">سجل الإسنادات</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-max text-sm">
                            <thead>
                                <tr class="border-b border-mist-100 text-xs text-mist-500 dark:border-ink-700">
                                    <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                                    <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الأصل</th>
                                    <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">من</th>
                                    <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">إلى</th>
                                    <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                                @forelse ($assetAssignmentHistory as $assignment)
                                    <tr>
                                        <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration + ($assetAssignmentHistory->currentPage() - 1) * $assetAssignmentHistory->perPage() }}</td>
                                        <td class="px-3 py-2 text-start">{{ $assignment->asset?->asset_code }} — {{ $assignment->asset?->name }}</td>
                                        <td class="px-3 py-2 text-start"><x-ui.ltr>{{ $assignment->assigned_at?->format('Y-m-d') }}</x-ui.ltr></td>
                                        <td class="px-3 py-2 text-start"><x-ui.ltr>{{ $assignment->returned_at?->format('Y-m-d') ?? 'نشط' }}</x-ui.ltr></td>
                                        <td class="px-3 py-2 text-center">
                                            @if ($assignment->returned_at)
                                                مُعاد ({{ $assignment->condition_on_return?->label() ?? '—' }})
                                            @else
                                                نشط
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <x-ui.table-empty :colspan="5" icon="archive" message="لا يوجد سجل إسنادات." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($assetAssignmentHistory->hasPages())
                        <div class="mt-3">{{ $assetAssignmentHistory->withQueryString()->appends(['tab' => 'assets'])->links() }}</div>
                    @endif
                </section>
            </div>
        @endcan
    </div>
</x-layouts.app>
