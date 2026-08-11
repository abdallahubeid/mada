<x-layouts.app title="تقييمات الأداء">
    @php
        $inputClasses = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $modeLabel = match ($viewerMode) {
            'owner' => 'نظرة المالك / الإدارة العليا',
            'hr' => 'عرض الموارد البشرية حسب الأقسام',
            default => 'عرض المدير المباشر (المرؤوسون فقط)',
        };
    @endphp

    <div class="space-y-6" x-data="{ periodType: @js($periodType->value) }">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تقييمات الأداء</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    {{ $modeLabel }} · الفترة: <span class="font-semibold text-ink-700 dark:text-mist-200">{{ $periodLabel }}</span>
                </p>
            </div>

            @if ($canApprove)
                <form
                    method="POST"
                    action="{{ route('hr.evaluations.approve') }}"
                    data-swal-confirm
                    data-swal-variant="success"
                    data-swal-title="اعتماد تقييمات الفترة؟"
                    data-swal-text="سيتم قفل جميع تقييمات هذه الفترة ولا يمكن تعديلها لاحقاً."
                    data-swal-confirm-button="نعم، اعتمد التقييمات"
                    data-swal-confirm-button="نعم، اعتمد"
                >
                    @csrf
                    <input type="hidden" name="period_type" value="{{ $periodType->value }}">
                    <input type="hidden" name="period_key" value="{{ $periodKey }}">
                    <button
                        type="submit"
                        @disabled($periodLocked)
                        class="inline-flex items-center rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        إعتماد التقييم
                    </button>
                </form>
            @endif
        </div>

        <form method="GET" action="{{ route('hr.evaluations.index') }}" class="grid gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:grid-cols-3 dark:border-ink-600 dark:bg-ink-800">
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-mist-500">نوع الفترة</label>
                <select name="period_type" x-model="periodType" class="{{ $inputClasses }}" onchange="this.form.submit()">
                    @foreach ($periodTypes as $type)
                        <option value="{{ $type->value }}" @selected($periodType === $type)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold text-mist-500">اختيار الفترة</label>
                <select name="period_key" class="{{ $inputClasses }}" onchange="this.form.submit()">
                    @foreach ($periodOptions as $option)
                        <option value="{{ $option['key'] }}" @selected($periodKey === $option['key'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($groups->every(fn ($group) => $group['employees']->isEmpty()))
            <div class="rounded-2xl border border-dashed border-mist-200 bg-white p-10 text-center dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا يوجد موظفون لعرضهم في نطاق صلاحيتك.</p>
                <p class="mt-1 text-sm text-mist-500">إن كنت مديراً مباشراً، تأكد من تعيين مرؤوسين عبر حقل المدير في ملفات الموظفين.</p>
            </div>
        @else
            <form method="POST" action="{{ route('hr.evaluations.upsert') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="period_type" value="{{ $periodType->value }}">
                <input type="hidden" name="period_key" value="{{ $periodKey }}">

                @foreach ($groups as $groupIndex => $group)
                    @continue($group['employees']->isEmpty())
                    <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-mist-100 bg-mist-50 px-4 py-3 dark:border-ink-700 dark:bg-ink-900">
                            <div>
                                <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">
                                    {{ $group['department']?->name ?? ($viewerMode === 'manager' ? 'المرؤوسون المباشرون' : 'بدون قسم') }}
                                </p>
                                @if ($group['head'])
                                    <p class="text-xs text-mist-500">
                                        رئيس القسم:
                                        <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $group['head']->full_name }}</span>
                                    </p>
                                @endif
                            </div>
                            <span class="rounded-full bg-emerald-400/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                                {{ $group['employees']->count() }} موظف
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                                <thead class="bg-white text-mist-500 dark:bg-ink-800 dark:text-mist-400">
                                    <tr>
                                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الموظف</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">التقييم (0–5)</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">ملاحظات</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                                    @foreach ($group['employees'] as $employee)
                                        @php
                                            $evaluation = $evaluations->get($employee->id);
                                            $locked = $evaluation?->isLocked() ?? false;
                                            $isHead = $group['head'] && (int) $group['head']->id === (int) $employee->id;
                                            $rowName = "rows[{$employee->id}]";
                                        @endphp
                                        <tr @class([
                                            'bg-emerald-400/[0.06]' => $isHead,
                                        ])>
                                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                            <td class="px-4 py-3 text-start">
                                                <input type="hidden" name="{{ $rowName }}[employee_id]" value="{{ $employee->id }}">
                                                <p class="font-medium text-ink-900 dark:text-ink-50">
                                                    {{ $employee->full_name }}
                                                    @if ($isHead)
                                                        <span class="ms-1 rounded-full bg-emerald-400 px-2 py-0.5 text-[10px] font-bold text-emerald-950">رئيس القسم</span>
                                                    @endif
                                                </p>
                                                <p class="text-xs text-mist-500">{{ $employee->job_title }}</p>
                                            </td>
                                            <td class="px-4 py-3 w-36 text-start">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="5"
                                                    name="{{ $rowName }}[rating]"
                                                    value="{{ old($rowName.'.rating', $evaluation?->rating) }}"
                                                    @disabled(! $canSubmit || $locked || $periodLocked)
                                                    class="{{ $inputClasses }}"
                                                >
                                            </td>
                                            <td class="px-4 py-3 text-start">
                                                <input
                                                    type="text"
                                                    name="{{ $rowName }}[notes]"
                                                    value="{{ old($rowName.'.notes', $evaluation?->notes) }}"
                                                    @disabled(! $canSubmit || $locked || $periodLocked)
                                                    placeholder="ملاحظة اختيارية..."
                                                    class="{{ $inputClasses }}"
                                                >
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="rounded-full bg-mist-100 px-2 py-0.5 text-[10px] font-semibold text-mist-600 dark:bg-ink-700 dark:text-mist-300">
                                                    {{ $evaluation?->status->label() ?? '—' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                @if ($canSubmit && ! $periodLocked)
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            name="intent"
                            value="draft"
                            class="rounded-xl border border-mist-200 px-4 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-mist-50 dark:border-ink-600 dark:text-mist-200 dark:hover:bg-ink-700"
                        >
                            حفظ كمسودة
                        </button>
                        <button
                            type="submit"
                            name="intent"
                            value="submit"
                            class="rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300"
                        >
                            إرسال التقييمات
                        </button>
                    </div>
                @elseif ($periodLocked)
                    <p class="text-sm text-mist-500">هذه الفترة معتمدة ومقفلة — لا يمكن تعديل التقييمات.</p>
                @endif
            </form>
        @endif
    </div>
</x-layouts.app>
