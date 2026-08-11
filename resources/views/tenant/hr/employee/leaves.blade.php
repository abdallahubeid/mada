@php
    use App\Domain\Tenancy\Enums\LeaveRequestStatus;

    $card = 'rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800';
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';

    $statusClasses = [
        LeaveRequestStatus::Pending->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        LeaveRequestStatus::Approved->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        LeaveRequestStatus::Rejected->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="طلبات الإجازة">
    @if ($employee === null)
        <div class="mx-auto max-w-2xl">
            <div class="{{ $card }} text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-400/15 text-2xl">📝</div>
                <h1 class="mt-4 font-display text-2xl font-bold text-ink-900 dark:text-ink-50">طلبات الإجازة</h1>
                <p class="mt-2 text-sm text-mist-500">
                    حسابك غير مرتبط بملف موظف، لذا لا تتوفر أرصدة أو طلبات إجازة. تواصل مع إدارة الموارد البشرية لربط حسابك.
                </p>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-5xl space-y-6" x-data="{ leaveOpen: {{ $errors->any() ? 'true' : 'false' }} }">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">طلبات الإجازة</h1>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                        أرصدتك المتاحة وسجل طلباتك — المتبقي {{ $remainingLeaveDays }} يوم عبر جميع الأنواع.
                    </p>
                </div>
                @can('hr.my_leaves.view')
                    <button type="button" @click="leaveOpen = true" class="inline-flex items-center rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                        طلب إجازة جديد
                    </button>
                @endcan
            </div>

            {{-- Balances --}}
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" data-testid="leave-balances">
                @forelse ($leaveBalances as $balance)
                    <div class="{{ $card }}">
                        <div class="flex items-baseline justify-between">
                            <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $balance['type']->name }}</p>
                            <p class="font-display text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $balance['remaining'] }}</p>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-mist-100 dark:bg-ink-700">
                            <div class="h-full rounded-full bg-emerald-400" style="width: {{ $balance['annual'] > 0 ? min(100, ($balance['remaining'] / $balance['annual']) * 100) : 0 }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-mist-500">مستخدم {{ $balance['used'] }} من {{ $balance['annual'] }} يوم</p>
                    </div>
                @empty
                    <div class="{{ $card }} sm:col-span-2 xl:col-span-3">
                        <p class="text-center text-sm text-mist-500">لا توجد أنواع إجازات معرّفة بعد.</p>
                    </div>
                @endforelse
            </div>

            {{-- History --}}
            <div class="space-y-4">
                <h2 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">سجل طلباتي</h2>
                <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                        <thead class="bg-mist-50 dark:bg-ink-900">
                            <tr>
                                <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">النوع</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">من</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">إلى</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الأيام</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                            @forelse ($leaveRequests as $leaveRequest)
                                <tr>
                                    <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 text-start">{{ $leaveRequest->leaveType?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 tabular-nums text-start"><x-ui.ltr>{{ $leaveRequest->start_date?->format('Y-m-d') }}</x-ui.ltr></td>
                                    <td class="px-4 py-3 tabular-nums text-start"><x-ui.ltr>{{ $leaveRequest->end_date?->format('Y-m-d') }}</x-ui.ltr></td>
                                    <td class="px-4 py-3 text-start">{{ $leaveRequest->days_count }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$leaveRequest->status->value] ?? ''])>
                                            {{ $leaveRequest->status->label() }}
                                        </span>
                                        @if ($leaveRequest->status === LeaveRequestStatus::Rejected && filled($leaveRequest->rejection_reason))
                                            <p class="mt-1 text-xs text-mist-500">{{ $leaveRequest->rejection_reason }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-ui.table-empty :colspan="6" icon="🌴" message="لا توجد طلبات إجازة." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $leaveRequests->withQueryString()->links() }}</div>
            </div>

            {{-- Request modal --}}
            @can('hr.my_leaves.view')
                <div x-show="leaveOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4" @keydown.escape.window="leaveOpen = false">
                    <div class="w-full max-w-lg rounded-2xl border border-mist-200 bg-white p-5 shadow-xl dark:border-ink-600 dark:bg-ink-800" @click.outside="leaveOpen = false">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-ink-900 dark:text-ink-50">طلب إجازة جديد</h3>
                            <button type="button" @click="leaveOpen = false" class="text-mist-500">إغلاق</button>
                        </div>
                        <form method="POST" action="{{ route('tenant.hr.my-leaves.store') }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <label for="leave_type_id" class="{{ $labelClass }}">نوع الإجازة</label>
                                <select id="leave_type_id" name="leave_type_id" required class="{{ $inputClass }}">
                                    @foreach ($leaveTypes as $type)
                                        <option value="{{ $type->id }}" @selected((string) old('leave_type_id') === (string) $type->id)>
                                            {{ $type->name }} ({{ $type->annual_days }} يوم)
                                        </option>
                                    @endforeach
                                </select>
                                @error('leave_type_id')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="start_date" class="{{ $labelClass }}">من</label>
                                    <input id="start_date" type="date" name="start_date" required dir="ltr" value="{{ old('start_date') }}" class="{{ $inputClass }}">
                                    @error('start_date')
                                        <p class="{{ $errorClass }}">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="end_date" class="{{ $labelClass }}">إلى</label>
                                    <input id="end_date" type="date" name="end_date" required dir="ltr" value="{{ old('end_date') }}" class="{{ $inputClass }}">
                                    @error('end_date')
                                        <p class="{{ $errorClass }}">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div>
                                <label for="reason" class="{{ $labelClass }}">السبب</label>
                                <textarea id="reason" name="reason" rows="2" class="{{ $inputClass }}">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <p class="{{ $errorClass }}">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="w-full rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                                إرسال الطلب
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    @endif
</x-layouts.app>
