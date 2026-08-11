@php
    use App\Domain\Finance\Enums\ExpenseStatus;

    $statusClasses = [
        ExpenseStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        ExpenseStatus::PendingApproval->value => 'bg-amber-400/15 text-amber-700 dark:text-amber-300',
        ExpenseStatus::Approved->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        ExpenseStatus::Rejected->value => 'bg-danger-solid/10 text-danger-solid',
        ExpenseStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
    ];

    $canDecide = auth()->user()?->can('finance.expenses.approve')
        && $expense->status === ExpenseStatus::PendingApproval;
@endphp

<x-layouts.app title="{{ $expense->title }}">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">{{ $expense->title }}</h1>
                    <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$expense->status->value] ?? ''])>
                        {{ $expense->status->label() }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    <x-ui.ltr>{{ $expense->expense_date?->format('Y-m-d') }}</x-ui.ltr>
                    @if ($expense->category) · {{ $expense->category->name }} @endif
                    @if ($expense->employee) · {{ $expense->employee->full_name }} @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('finance.expenses.manage')
                    @if ($expense->status->isEditable())
                        <a href="{{ route('finance.expenses.edit', $expense) }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">تعديل</a>
                        <form method="POST" action="{{ route('finance.expenses.submit', $expense) }}"
                              data-swal-confirm
                              data-swal-variant="info"
                              data-swal-title="رفع المصروف للاعتماد؟"
                              data-swal-text="سيُرسل المصروف للمراجعة، ولن تتمكن من تعديله حتى يتم اعتماده أو إعادته إليك."
                              data-swal-confirm-button="نعم، ارفع للاعتماد">
                            @csrf
                            <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">رفع للاعتماد</button>
                        </form>
                    @endif
                @endcan

                @if ($canDecide)
                    <form method="POST" action="{{ route('finance.expenses.approve', $expense) }}"
                          data-swal-confirm
                          data-swal-variant="success"
                          data-swal-title="اعتماد هذا المصروف؟"
                          data-swal-text="سيصبح المصروف معتمداً ويُحتسب ضمن تكاليف المؤسسة، ولن يمكن تعديله بعد ذلك."
                          data-swal-confirm-button="نعم، اعتمد المصروف">
                        @csrf
                        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">اعتماد</button>
                    </form>
                @endif

                @can('finance.expenses.pay')
                    @if ($expense->isDisbursable())
                        <form method="POST" action="{{ route('finance.expenses.disburse', $expense) }}"
                              data-swal-confirm
                              data-swal-variant="info"
                              data-swal-title="تسجيل صرف المصروف؟"
                              data-swal-text="سيتم تسجيل رد المبلغ لصاحب المطالبة. النظام لا يحوّل الأموال فعلياً."
                              data-swal-confirm-button="نعم، سجّل الصرف">
                            @csrf
                            <button type="submit" class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-400">تسجيل الصرف</button>
                        </form>
                    @endif
                @endcan

                @if ($expense->receipt_path)
                    <a href="{{ Storage::disk('custom')->url($expense->receipt_path) }}" target="_blank" rel="noopener" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">الإيصال</a>
                @endif
            </div>
        </div>

        @if ($expense->rejection_reason)
            <div class="rounded-2xl border border-danger-solid/40 bg-danger-solid/5 p-4">
                <p class="text-sm font-semibold text-danger-solid">سبب الرفض</p>
                <p class="mt-1 text-sm text-ink-700 dark:text-mist-200">{{ $expense->rejection_reason }}</p>
            </div>
        @endif

        @if ($canDecide)
            <form method="POST" action="{{ route('finance.expenses.reject', $expense) }}" class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                @csrf
                <label for="rejection_reason" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">رفض المصروف</label>
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
                ['label' => 'القيمة', 'value' => $expense->amount, 'money' => true],
                ['label' => 'قابل للاسترداد', 'value' => $expense->is_claimable ? 'نعم' : 'لا', 'money' => false],
                ['label' => 'قدّمه', 'value' => $expense->submitter?->name ?? '—', 'money' => false],
                ['label' => 'قرّره', 'value' => $expense->decider?->name ?? '—', 'money' => false],
            ] as $tile)
                <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ $tile['label'] }}</p>
                    <p class="mt-2 font-display text-lg font-bold text-ink-900 dark:text-ink-50">
                        @if ($tile['money'])
                            <x-ui.money :amount="$tile['value']" :currency="$expense->currency" />
                        @else
                            {{ $tile['value'] }}
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        @if ($expense->description)
            <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">التفاصيل</p>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $expense->description }}</p>
            </div>
        @endif

        @if ($expense->approvals->isNotEmpty())
            <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">القرار</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">بواسطة</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @foreach ($expense->approvals as $approval)
                            <tr>
                                <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 text-start text-ink-700 dark:text-mist-200">{{ $approval->status->label() }}</td>
                                <td class="px-4 py-3 text-start text-mist-500">{{ $approval->decidedBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-start text-mist-500"><x-ui.ltr>{{ $approval->decided_at?->format('Y-m-d H:i') ?? '—' }}</x-ui.ltr></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
