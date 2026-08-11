@php
    use App\Domain\Finance\Enums\PayslipLineItemKind;
@endphp

<x-layouts.app title="قسيمة راتب {{ $payslip->employee_name }}">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">{{ $payslip->employee_name }}</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    {{ $payslip->job_title ?? '—' }}
                    @if ($payslip->department_name)
                        · {{ $payslip->department_name }}
                    @endif
                    · فترة <x-ui.ltr>{{ $payslip->payrollRun?->period }}</x-ui.ltr>
                </p>
            </div>
            <a href="{{ route('finance.payslips.print', $payslip) }}" target="_blank" rel="noopener" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">
                نسخة للطباعة
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'أساس الاحتساب', 'value' => $payslip->pay_basis->label()],
                ['label' => 'أيام العمل المجدولة', 'value' => $payslip->scheduled_days],
                ['label' => 'أيام الحضور', 'value' => $payslip->present_days],
                ['label' => 'أيام الغياب', 'value' => $payslip->absent_days],
            ] as $tile)
                <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ $tile['label'] }}</p>
                    <p class="mt-2 font-display text-lg font-bold text-ink-900 dark:text-ink-50">
                        @if (is_int($tile['value']))
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
                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">البند</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">النوع</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">القيمة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    <tr>
                        <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">1</td>
                        <td class="px-4 py-3 text-start font-medium text-ink-900 dark:text-ink-50">الراتب الأساسي</td>
                        <td class="px-4 py-3 text-center text-mist-500">أساسي</td>
                        <td class="px-4 py-3 text-end text-ink-700 dark:text-mist-200"><x-ui.money :amount="$payslip->base_amount" /></td>
                    </tr>
                    @if ($payslip->absence_deduction !== 0)
                        <tr>
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">2</td>
                            <td class="px-4 py-3 text-start font-medium text-ink-900 dark:text-ink-50">
                                خصم الغياب
                                <span class="text-xs text-mist-500">({{ $payslip->absent_days }} يوم)</span>
                            </td>
                            <td class="px-4 py-3 text-center text-mist-500">استقطاع</td>
                            <td class="px-4 py-3 text-end text-danger-solid"><x-ui.money :amount="$payslip->absence_deduction" :signed="true" /></td>
                        </tr>
                    @endif
                    @forelse ($payslip->lineItems as $lineItem)
                        <tr>
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">
                                {{ $loop->iteration + ($payslip->absence_deduction !== 0 ? 2 : 1) }}
                            </td>
                            <td class="px-4 py-3 text-start text-ink-700 dark:text-mist-200">{{ $lineItem->label }}</td>
                            <td class="px-4 py-3 text-center text-mist-500">{{ $lineItem->kind->label() }}</td>
                            <td @class([
                                'px-4 py-3 text-end',
                                'text-emerald-600 dark:text-emerald-400' => $lineItem->kind === PayslipLineItemKind::Allowance,
                                'text-danger-solid' => $lineItem->kind === PayslipLineItemKind::Deduction,
                            ])>
                                <x-ui.money :amount="$lineItem->amount" :signed="true" />
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
                <tfoot class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-end text-sm font-semibold text-ink-700 dark:text-mist-200">الإجمالي</td>
                        <td class="px-4 py-3 text-end text-sm font-semibold text-ink-700 dark:text-mist-200">
                            <x-ui.money :amount="$payslip->gross_amount" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-end font-display text-base font-bold text-ink-900 dark:text-ink-50">صافي المستحق</td>
                        <td class="px-4 py-3 text-end font-display text-base font-bold text-ink-900 dark:text-ink-50">
                            <x-ui.money :amount="$payslip->net_amount" :currency="$payslip->pay_currency" />
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @can('finance.payroll.view_any')
            <a href="{{ route('finance.payroll-runs.show', $payslip->payroll_run_id) }}" class="inline-block text-sm font-semibold text-emerald-600 hover:underline dark:text-emerald-400">
                ← العودة إلى المسيرة
            </a>
        @endcan
    </div>
</x-layouts.app>
