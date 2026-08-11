@php
    use App\Domain\Finance\Enums\PayrollRunStatus;

    $statusClasses = [
        PayrollRunStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        PayrollRunStatus::PendingApproval->value => 'bg-amber-400/15 text-amber-700 dark:text-amber-300',
        PayrollRunStatus::Approved->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        PayrollRunStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
        PayrollRunStatus::Cancelled->value => 'bg-danger-solid/10 text-danger-solid',
    ];

    $canApprove = auth()->user()?->can('finance.payroll.approve')
        && $run->status === PayrollRunStatus::PendingApproval;
@endphp

<x-layouts.app title="مسيرة رواتب {{ $run->period }}">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">
                        مسيرة رواتب <x-ui.ltr>{{ $run->period }}</x-ui.ltr>
                    </h1>
                    <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$run->status->value] ?? ''])>
                        {{ $run->status->label() }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    <x-ui.ltr>{{ $run->period_start?->format('Y-m-d') }}</x-ui.ltr>
                    —
                    <x-ui.ltr>{{ $run->period_end?->format('Y-m-d') }}</x-ui.ltr>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('finance.payroll.prepare')
                    @if ($run->status->isEditable())
                        <a href="{{ route('finance.payroll-runs.edit', $run) }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">تعديل</a>

                        <form method="POST" action="{{ route('finance.payroll-runs.recalculate', $run) }}">
                            @csrf
                            <button type="submit" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">إعادة الاحتساب</button>
                        </form>

                        <form method="POST" action="{{ route('finance.payroll-runs.submit', $run) }}"
                              data-swal-confirm
                              data-swal-variant="info"
                              data-swal-title="رفع المسيرة للاعتماد؟"
                              data-swal-text="ستُرسل المسيرة إلى المعتمِد للمراجعة، ولن تتمكن من تعديلها حتى يتم اعتمادها أو إعادتها إليك."
                              data-swal-confirm-button="نعم، ارفع للاعتماد">
                            @csrf
                            <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">رفع للاعتماد</button>
                        </form>
                    @endif
                @endcan

                @if ($canApprove)
                    <form method="POST" action="{{ route('finance.payroll-runs.approve', $run) }}"
                          data-swal-confirm
                          data-swal-variant="success"
                          data-swal-title="اعتماد مسيرة الرواتب؟"
                          data-swal-text="سيتم قفل المسيرة نهائياً ولن يمكن تعديل أي مبلغ فيها. أي تصحيح لاحق يتم عبر قيد تسوية في مسيرة تالية."
                          data-swal-confirm-button="نعم، اعتمد المسيرة">
                        @csrf
                        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">اعتماد</button>
                    </form>
                @endif

                @can('finance.payroll.pay')
                    @if ($run->status === PayrollRunStatus::Approved)
                        <form method="POST" action="{{ route('finance.payroll-runs.disburse', $run) }}"
                              data-swal-confirm
                              data-swal-variant="info"
                              data-swal-title="تسجيل صرف المسيرة؟"
                              data-swal-text="سيتم تسجيل واقعة الصرف وإشعار الموظفين بجاهزية قسائم رواتبهم. النظام لا يحوّل الأموال فعلياً."
                              data-swal-confirm-button="نعم، سجّل الصرف">
                            @csrf
                            <button type="submit" class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-400">تسجيل الصرف</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        @if ($run->rejection_reason)
            <div class="rounded-2xl border border-danger-solid/40 bg-danger-solid/5 p-4">
                <p class="text-sm font-semibold text-danger-solid">سبب الرفض</p>
                <p class="mt-1 text-sm text-ink-700 dark:text-mist-200">{{ $run->rejection_reason }}</p>
            </div>
        @endif

        @if ($canApprove)
            <form method="POST" action="{{ route('finance.payroll-runs.reject', $run) }}" class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                @csrf
                <label for="rejection_reason" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">رفض المسيرة وإعادتها للمُعِد</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input id="rejection_reason" type="text" name="rejection_reason" required placeholder="سبب الرفض" value="{{ old('rejection_reason') }}" class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <button type="submit" class="rounded-xl border border-danger-solid px-4 py-2 text-sm font-semibold text-danger-solid">رفض</button>
                </div>
                @error('rejection_reason')
                    <p class="mt-1.5 text-xs text-danger-solid">{{ $message }}</p>
                @enderror
            </form>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'عدد الموظفين', 'value' => $run->payslip_count, 'money' => false],
                ['label' => 'الرواتب الأساسية', 'value' => $run->total_base, 'money' => true],
                ['label' => 'خصومات الغياب', 'value' => $run->total_absence_deduction, 'money' => true],
                ['label' => 'صافي المستحق', 'value' => $run->total_net, 'money' => true],
            ] as $tile)
                <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ $tile['label'] }}</p>
                    <p class="mt-2 font-display text-xl font-bold text-ink-900 dark:text-ink-50">
                        @if ($tile['money'])
                            <x-ui.money :amount="$tile['value']" :currency="$run->currency" />
                        @else
                            <x-ui.ltr>{{ $tile['value'] }}</x-ui.ltr>
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الموظف</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">أيام العمل</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الغياب</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الأساسي</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">خصم الغياب</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الإجمالي</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الصافي</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($payslips as $payslip)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">
                                {{ $loop->iteration + ($payslips->currentPage() - 1) * $payslips->perPage() }}
                            </td>
                            <td class="px-4 py-3 text-start font-medium text-ink-900 dark:text-ink-50">{{ $payslip->employee_name }}</td>
                            <td class="px-4 py-3 text-center text-mist-500"><x-ui.ltr>{{ $payslip->scheduled_days }}</x-ui.ltr></td>
                            <td class="px-4 py-3 text-center text-mist-500"><x-ui.ltr>{{ $payslip->absent_days }}</x-ui.ltr></td>
                            <td class="px-4 py-3 text-end text-mist-500"><x-ui.money :amount="$payslip->base_amount" /></td>
                            <td class="px-4 py-3 text-end text-danger-solid"><x-ui.money :amount="$payslip->absence_deduction" /></td>
                            <td class="px-4 py-3 text-end text-mist-500"><x-ui.money :amount="$payslip->gross_amount" /></td>
                            <td class="px-4 py-3 text-end font-semibold text-ink-900 dark:text-ink-50">
                                <x-ui.money :amount="$payslip->net_amount" :currency="$payslip->pay_currency" />
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('finance.payslips.show', $payslip) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">تفاصيل</a>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="9" icon="🧾" message="لا توجد قسائم رواتب في هذه المسيرة." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $payslips->links() }}</div>

        @if ($adjustments->isNotEmpty())
            <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <div class="border-b border-mist-200 p-4 dark:border-ink-600">
                    <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">قيود التسوية المحمولة على هذه المسيرة</h2>
                    <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">تصحيحات لفترات مقفلة — الفترة الأصلية لم تُمَس.</p>
                </div>
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الموظف</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الفترة الأصلية</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">السبب</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">القيمة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @foreach ($adjustments as $adjustment)
                            <tr>
                                <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 text-start text-ink-700 dark:text-mist-200">{{ $adjustment->employee_name }}</td>
                                <td class="px-4 py-3 text-start text-mist-500"><x-ui.ltr>{{ $adjustment->original_period }}</x-ui.ltr></td>
                                <td class="px-4 py-3 text-start text-mist-500">{{ $adjustment->reason }}</td>
                                <td @class([
                                    'px-4 py-3 text-end font-semibold',
                                    'text-danger-solid' => $adjustment->isClawback(),
                                    'text-emerald-600 dark:text-emerald-400' => ! $adjustment->isClawback(),
                                ])>
                                    <x-ui.money :amount="$adjustment->amount" :signed="true" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @can('finance.payroll.prepare')
            @if ($run->status->isEditable() && $correctablePayslips->isNotEmpty())
                <form method="POST" action="{{ route('finance.payroll-runs.adjustments.store', $run) }}" class="space-y-3 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    @csrf
                    <div>
                        <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">تسجيل قيد تسوية لفترة مقفلة</h2>
                        <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">
                            المسيرات المعتمدة لا تُعدّل. يُسجَّل التصحيح هنا ويُصرف مع هذه المسيرة (BR-603).
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <label for="original_payslip_id" class="mb-1.5 block text-xs font-medium text-mist-500">القسيمة الأصلية</label>
                            <select id="original_payslip_id" name="original_payslip_id" required class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                                @foreach ($correctablePayslips as $candidate)
                                    <option value="{{ $candidate->id }}">
                                        {{ $candidate->employee_name }} — {{ $candidate->payrollRun?->period }}
                                    </option>
                                @endforeach
                            </select>
                            @error('original_payslip_id')
                                <p class="mt-1.5 text-xs text-danger-solid">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="amount" class="mb-1.5 block text-xs font-medium text-mist-500">القيمة (سالب = استرداد)</label>
                            <input id="amount" type="number" step="0.01" dir="ltr" name="amount" required value="{{ old('amount') }}" class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-end text-sm tabular-nums dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                            @error('amount')
                                <p class="mt-1.5 text-xs text-danger-solid">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="reason" class="mb-1.5 block text-xs font-medium text-mist-500">السبب</label>
                            <input id="reason" type="text" name="reason" required value="{{ old('reason') }}" class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                            @error('reason')
                                <p class="mt-1.5 text-xs text-danger-solid">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">تسجيل التسوية</button>
                    </div>
                </form>
            @endif
        @endcan

        @if ($run->notes)
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">ملاحظات</p>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $run->notes }}</p>
            </div>
        @endif
    </div>
</x-layouts.app>
