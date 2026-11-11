@php
    use App\Domain\Tenancy\Enums\LeaveRequestStatus;

    $statusClasses = [
        LeaveRequestStatus::Pending->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        LeaveRequestStatus::Approved->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        LeaveRequestStatus::Rejected->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="إدارة الإجازات">
    <div class="space-y-6" x-data="{ typeOpen: false, requestOpen: false }">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إدارة الإجازات</h1>
                <p class="mt-1 text-sm text-mist-500">أنواع الإجازات وطلبات الفريق مع الاعتماد والرفض.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('hr.leaves.manage_types')
                    <button type="button" @click="typeOpen = true" class="ui-btn ui-btn-secondary">نوع إجازة</button>
                @endcan
                @can('hr.leaves.create')
                    <button type="button" @click="requestOpen = true" class="ui-btn ui-btn-primary">طلب إجازة</button>
                @endcan
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($leaveTypes as $type)
                <div class="ui-card p-4">
                    <p class="font-semibold text-ink-900 dark:text-ink-50">{{ $type->name }}</p>
                    <p class="mt-1 text-sm text-mist-500">{{ $type->annual_days }} يوم سنوياً · {{ $type->requires_approval ? 'يتطلب اعتماداً' : 'تلقائي' }}</p>
                </div>
            @endforeach
        </div>

        <form method="GET" action="{{ route('hr.leaves.index') }}" class="ui-card grid gap-3 p-4 sm:grid-cols-3">
            <div>
                <label for="f-index-status-1" class="mb-1.5 block text-xs text-mist-500">الحالة</label>
                <select id="f-index-status-1" name="status" class="ui-control w-full">
                    <option value="all" @selected($filters['status'] === 'all')>الكل</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-index-employee-id-2" class="mb-1.5 block text-xs text-mist-500">الموظف</label>
                <select id="f-index-employee-id-2" name="employee_id" class="ui-control w-full">
                    <option value="all" @selected($filters['employee_id'] === 'all')>الكل</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($filters['employee_id'] === (string) $employee->id)>{{ $employee->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="ui-btn ui-btn-secondary">تصفية</button>
            </div>
        </form>

        <div class="ui-card w-full overflow-x-auto">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الموظف</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">النوع</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الفترة</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الأيام</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($requests as $leaveRequest)
                        <tr>
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-medium text-start">{{ $leaveRequest->employee?->full_name ?? '—' }}</td>
                            <td class="px-3 py-2 text-start">{{ $leaveRequest->leaveType?->name ?? '—' }}</td>
                            <td class="px-3 py-2 tabular-nums text-mist-500 text-start"><x-ui.ltr>{{ $leaveRequest->start_date?->format('Y-m-d') }} → {{ $leaveRequest->end_date?->format('Y-m-d') }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-start">{{ $leaveRequest->days_count }}</td>
                            <td class="px-3 py-2 text-center">
                                <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$leaveRequest->status->value] ?? ''])>
                                    {{ $leaveRequest->status->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex justify-center gap-2">
                                    @if ($leaveRequest->status === LeaveRequestStatus::Pending)
                                        @can('hr.leaves.approve')
                                            <form method="POST" action="{{ route('hr.leaves.approve', $leaveRequest) }}">
                                                @csrf
                                                <button type="submit" class="ui-btn ui-btn-secondary ui-btn-sm">اعتماد</button>
                                            </form>
                                            <form method="POST" action="{{ route('hr.leaves.reject', $leaveRequest) }}">
                                                @csrf
                                                <button type="submit" class="ui-btn ui-btn-danger-outline ui-btn-sm">رفض</button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="7" icon="sun" message="لا توجد طلبات إجازة." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $requests->links() }}</div>

        @can('hr.leaves.manage_types')
            <div x-show="typeOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white p-4 shadow-xl dark:bg-ink-800" @click.outside="typeOpen = false">
                    <h3 class="font-semibold">إضافة نوع إجازة</h3>
                    <form method="POST" action="{{ route('hr.leaves.types.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="text" name="name" required placeholder="الاسم" class="ui-control w-full">
                        <input type="number" name="annual_days" value="14" min="1" required class="ui-control w-full">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="requires_approval" value="1" checked>
                            يتطلب اعتماداً
                        </label>
                        <button type="submit" class="w-full ui-btn ui-btn-primary">حفظ</button>
                    </form>
                </div>
            </div>
        @endcan

        @can('hr.leaves.create')
            <div x-show="requestOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white p-4 shadow-xl dark:bg-ink-800" @click.outside="requestOpen = false">
                    <h3 class="font-semibold">طلب إجازة</h3>
                    <form method="POST" action="{{ route('hr.leaves.requests.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <select name="employee_id" required class="ui-control w-full">
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                        <select name="leave_type_id" required class="ui-control w-full">
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input type="date" name="start_date" required dir="ltr" class="ui-control w-full">
                            <input type="date" name="end_date" required dir="ltr" class="ui-control w-full">
                        </div>
                        <textarea name="reason" rows="2" placeholder="السبب" class="ui-control w-full"></textarea>
                        <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
                            <input type="checkbox" name="requires_manager_escalation" value="1" class="rounded border-mist-300 text-brand-500 focus:ring-brand-500">
                            يتطلب تصعيد المدير (اعتماد متعدد المستويات)
                        </label>
                        <div>
                            <label for="f-index-approval-level-3" class="mb-1 block text-xs text-mist-500">عدد مستويات الاعتماد</label>
                            <input id="f-index-approval-level-3" type="number" name="approval_level" min="1" max="5" value="2" class="ui-control w-full">
                        </div>
                        <button type="submit" class="w-full ui-btn ui-btn-primary">إرسال</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
</x-layouts.app>
