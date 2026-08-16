@php
    use App\Domain\Finance\Enums\SettlementStatus;

    $statusClasses = [
        SettlementStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        SettlementStatus::PendingApproval->value => 'bg-amber-400/15 text-amber-700 dark:text-amber-300',
        SettlementStatus::Approved->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        SettlementStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
        SettlementStatus::Cancelled->value => 'bg-danger-solid/10 text-danger-solid',
    ];

    $lines = [
        ['label' => 'مكافأة نهاية الخدمة', 'value' => $settlement->eosb_amount],
        ['label' => 'بدل إجازات غير مستخدمة ('.$settlement->unused_leave_days.' يوم)', 'value' => $settlement->leave_payout_amount],
        ['label' => 'راتب الشهر الأخير', 'value' => $settlement->prorated_salary_amount],
        ['label' => 'استقطاع سلف', 'value' => $settlement->loan_deduction_amount],
        ['label' => 'استقطاعات أخرى', 'value' => $settlement->other_deduction_amount],
    ];
@endphp

<x-layouts.app title="تسوية {{ $settlement->employee_name }}">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">{{ $settlement->employee_name }}</h1>
                    <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$settlement->status->value] ?? ''])>
                        {{ $settlement->status->label() }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    {{ $settlement->job_title ?? '—' }}
                    @if ($settlement->department_name) · {{ $settlement->department_name }} @endif
                    · {{ $settlement->reason->label() }}
                    · آخر يوم عمل <x-ui.ltr>{{ $settlement->last_working_day?->format('Y-m-d') }}</x-ui.ltr>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('finance.offboarding.print', $settlement) }}" target="_blank" rel="noopener" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600">نسخة للطباعة</a>

                @can('finance.offboarding.manage')
                    @if ($settlement->status->isEditable())
                        <form method="POST" action="{{ route('finance.offboarding.submit', $settlement) }}"
                              data-swal-confirm
                              data-swal-variant="info"
                              data-swal-title="رفع التسوية للاعتماد؟"
                              data-swal-text="ستُرسل التسوية للمراجعة، ولن تتمكن من تعديل مبالغها حتى يتم اعتمادها."
                              data-swal-confirm-button="نعم، ارفع للاعتماد">
                            @csrf
                            <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow hover:bg-brand-600">رفع للاعتماد</button>
                        </form>
                    @endif
                @endcan

                @can('finance.offboarding.approve')
                    @if ($settlement->status === SettlementStatus::PendingApproval)
                        <form method="POST" action="{{ route('finance.offboarding.approve', $settlement) }}"
                              data-swal-confirm
                              data-swal-variant="success"
                              data-swal-title="اعتماد تسوية نهاية الخدمة؟"
                              data-swal-text="سيتم قفل التسوية نهائياً ولن يمكن تعديل أي مبلغ فيها. الخطوة التالية هي الصرف وإنهاء الخدمة."
                              data-swal-confirm-button="نعم، اعتمد التسوية">
                            @csrf
                            <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow hover:bg-brand-600">اعتماد</button>
                        </form>
                    @endif

                    @if ($settlement->status === SettlementStatus::Approved)
                        {{--
                            Warning variant, not danger: this ends an employment
                            relationship — significant and irreversible — but it
                            deletes nothing. The copy states each consequence
                            explicitly rather than leaning on a delete template.
                        --}}
                        <form method="POST" action="{{ route('finance.offboarding.disburse', $settlement) }}"
                              data-swal-confirm
                              data-swal-variant="warning"
                              data-swal-title="صرف التسوية وإنهاء خدمة الموظف؟"
                              data-swal-text="سيتم تسجيل صرف المستحقات، وإنهاء عقد الموظف، وإيقاف إدراجه في مسيرات الرواتب القادمة، وتعطيل حسابه على المنصة. لا يمكن التراجع عن هذه الخطوة."
                              data-swal-confirm-button="نعم، اصرف وأنهِ الخدمة">
                            @csrf
                            <button type="submit" class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-400">صرف وإنهاء الخدمة</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        @if ($settlement->status === SettlementStatus::Approved)
            <div class="rounded-2xl border border-amber-300/60 bg-amber-50/80 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                صرف التسوية سيُنهي عقد الموظف ويوقف إدراجه في مسيرات الرواتب القادمة، ويُعطّل حسابه على المنصة.
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'مدة الخدمة', 'value' => $settlement->serviceYearsLabel()],
                ['label' => 'أساس الاحتساب', 'value' => $settlement->pay_basis->label()],
                ['label' => 'تاريخ الالتحاق', 'value' => $settlement->joining_date?->format('Y-m-d') ?? '—', 'ltr' => true],
                ['label' => 'أيام إجازات متبقية', 'value' => $settlement->unused_leave_days, 'ltr' => true],
            ] as $tile)
                <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ $tile['label'] }}</p>
                    <p class="mt-2 font-display text-lg font-medium text-ink-900 dark:text-ink-50">
                        @if ($tile['ltr'] ?? false)
                            <x-ui.ltr>{{ $tile['value'] }}</x-ui.ltr>
                        @else
                            {{ $tile['value'] }}
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">البند</th>
                        <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">القيمة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @foreach ($lines as $line)
                        <tr>
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 text-start text-ink-700 dark:text-mist-200">{{ $line['label'] }}</td>
                            <td @class([
                                'px-4 py-3 text-end',
                                'text-danger-solid' => $line['value'] < 0,
                                'text-ink-700 dark:text-mist-200' => $line['value'] >= 0,
                            ])>
                                <x-ui.money :amount="$line['value']" :signed="true" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <td colspan="2" class="px-3 py-2 text-end font-display text-base font-medium text-ink-900 dark:text-ink-50">صافي التسوية</td>
                        <td class="px-3 py-2 text-end font-display text-base font-medium text-ink-900 dark:text-ink-50">
                            <x-ui.money :amount="$settlement->total_amount" :currency="$settlement->currency" />
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{--
            The rules this settlement was computed under, read from its own
            snapshot rather than from current settings. Editing the settings
            afterwards must never change what this record explains.
        --}}
        @php $appliedPolicy = $settlement->eosbPolicy(); @endphp
        <details class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <summary class="cursor-pointer text-sm font-semibold text-ink-900 dark:text-ink-50">قواعد نهاية الخدمة المطبَّقة على هذه التسوية</summary>

            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-mist-500 dark:text-mist-400">احتساب المكافأة</dt>
                    <dd class="font-medium text-ink-700 dark:text-mist-200">{{ $appliedPolicy->enabled ? 'مفعّل' : 'معطّل' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-mist-500 dark:text-mist-400">حد الشريحة</dt>
                    <dd class="font-medium text-ink-700 dark:text-mist-200"><x-ui.ltr>{{ $appliedPolicy->tierBoundaryMonths }}</x-ui.ltr> شهر</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-mist-500 dark:text-mist-400">نسبة الشريحة الأولى</dt>
                    <dd class="font-medium text-ink-700 dark:text-mist-200"><x-ui.ltr>{{ number_format($appliedPolicy->lowerTierBps / 100, 2) }}%</x-ui.ltr></dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-mist-500 dark:text-mist-400">نسبة الشريحة الثانية</dt>
                    <dd class="font-medium text-ink-700 dark:text-mist-200"><x-ui.ltr>{{ number_format($appliedPolicy->upperTierBps / 100, 2) }}%</x-ui.ltr></dd>
                </div>
                <div class="flex items-center justify-between gap-2 sm:col-span-2">
                    <dt class="text-mist-500 dark:text-mist-400">تدرّج الاستقالة</dt>
                    <dd class="font-medium text-ink-700 dark:text-mist-200">
                        <x-ui.ltr>{{ collect($appliedPolicy->resignationTaper)->map(fn (array $band): string => $band['months'].'m → '.number_format($band['bps'] / 100, 2).'%')->implode(' · ') }}</x-ui.ltr>
                    </dd>
                </div>
            </dl>
        </details>

        @if ($settlement->notes)
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">ملاحظات</p>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $settlement->notes }}</p>
            </div>
        @endif
    </div>
</x-layouts.app>
