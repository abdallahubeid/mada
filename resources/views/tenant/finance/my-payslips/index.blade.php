@php
    use App\Domain\Finance\Enums\PayrollRunStatus;

    $statusClasses = [
        PayrollRunStatus::Approved->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        PayrollRunStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
    ];
@endphp

<x-layouts.app title="قسائم رواتبي">
    <div class="space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">قسائم رواتبي</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                سجل رواتبك المعتمدة. لا تظهر المسيرات تحت الإعداد حتى يتم اعتمادها.
            </p>
        </div>

        @unless ($hasEmployeeProfile)
            {{-- Graceful notice, not a 403: admins legitimately have no employee profile. --}}
            <div class="rounded-2xl border border-mist-200 bg-white p-6 text-center shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-mist-100 text-mist-400 dark:text-mist-500 dark:bg-ink-900" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm4.5 4.5h.008v.008h-.008V13.5Z" /></svg></span>
                <p class="mt-3 text-sm font-medium text-ink-700 dark:text-mist-200">لا يوجد ملف موظف مرتبط بحسابك.</p>
                <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">قسائم الرواتب تظهر للموظفين المرتبطين بملف موظف في المؤسسة.</p>
            </div>
        @else
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800 sm:max-w-xs">
                <p class="text-xs font-medium text-mist-500 dark:text-mist-400">إجمالي ما تم صرفه</p>
                <p class="mt-2 font-display text-xl font-medium text-ink-900 dark:text-ink-50">
                    <x-ui.money :amount="$lifetimeNet" />
                </p>
            </div>

            <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">الفترة</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">أيام العمل</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">الغياب</th>
                            <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">الإجمالي</th>
                            <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">الصافي</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">الحالة</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($payslips as $payslip)
                            <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                                <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">
                                    {{ $loop->iteration + ($payslips->currentPage() - 1) * $payslips->perPage() }}
                                </td>
                                <td class="px-3 py-2 text-start font-medium text-ink-900 dark:text-ink-50">
                                    <x-ui.ltr>{{ $payslip->payrollRun?->period }}</x-ui.ltr>
                                </td>
                                <td class="px-3 py-2 text-center text-mist-500"><x-ui.ltr>{{ $payslip->scheduled_days }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-center text-mist-500"><x-ui.ltr>{{ $payslip->absent_days }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-end text-mist-500"><x-ui.money :amount="$payslip->gross_amount" /></td>
                                <td class="px-3 py-2 text-end font-semibold text-ink-900 dark:text-ink-50">
                                    <x-ui.money :amount="$payslip->net_amount" :currency="$payslip->pay_currency" />
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$payslip->payrollRun?->status->value] ?? ''])>
                                        {{ $payslip->payrollRun?->status->label() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('finance.payslips.show', $payslip) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600">عرض</a>
                                        <a href="{{ route('finance.payslips.print', $payslip) }}" target="_blank" rel="noopener" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600">طباعة</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="8" icon="receipt" message="لا توجد قسائم رواتب معتمدة بعد." hint="ستظهر قسيمتك هنا فور اعتماد مسيرة الرواتب." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $payslips->links() }}</div>
        @endunless
    </div>
</x-layouts.app>
