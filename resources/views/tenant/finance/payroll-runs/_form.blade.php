@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';

    $isCreate = $method === 'POST';
    $lineItems = $lineItems ?? collect();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($isCreate)
        <div>
            <label for="period" class="{{ $labelClass }}">فترة المسيرة</label>
            <input id="period" type="month" name="period" dir="ltr" required value="{{ old('period', $run->period) }}" class="{{ $inputClass }}">
            <p class="mt-1.5 text-xs text-mist-500 dark:text-mist-400">
                يجب تسوية سجل العمل لهذه الفترة أولاً، وأن تكون جميع العقود النشطة مسعّرة.
            </p>
            @error('period')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    @else
        <div class="rounded-xl border border-mist-200 bg-mist-50 p-4 dark:border-ink-600 dark:bg-ink-900">
            <p class="text-sm text-ink-700 dark:text-mist-200">
                الفترة: <x-ui.ltr class="font-semibold">{{ $run->period }}</x-ui.ltr>
            </p>
            <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">
                الرواتب الأساسية وخصومات الغياب تُحتسب من سجل العمل ولا تُعدّل يدوياً. يمكن تعديل بنود البدلات والاستقطاعات فقط.
            </p>
        </div>

        @if ($lineItems->isNotEmpty())
            <div>
                <h3 class="mb-2 text-sm font-semibold text-ink-900 dark:text-ink-50">بنود البدلات والاستقطاعات</h3>
                <div class="w-full overflow-x-auto rounded-xl border border-mist-200 dark:border-ink-600">
                    <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                        <thead class="bg-mist-50 dark:bg-ink-900">
                            <tr>
                                <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                                <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">الموظف</th>
                                <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">البند</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">النوع</th>
                                <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">القيمة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                            @foreach ($lineItems as $lineItem)
                                <tr>
                                    <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 text-start text-ink-700 dark:text-mist-200">{{ $lineItem->payslip?->employee_name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-start text-ink-700 dark:text-mist-200">{{ $lineItem->label }}</td>
                                    <td class="px-3 py-2 text-center text-mist-500">{{ $lineItem->kind->label() }}</td>
                                    <td class="px-3 py-2 text-end">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            dir="ltr"
                                            name="line_items[{{ $lineItem->id }}]"
                                            value="{{ old('line_items.'.$lineItem->id, number_format(abs($lineItem->amount) / 100, 2, '.', '')) }}"
                                            class="w-32 rounded-lg border border-mist-200 bg-white px-2 py-1.5 text-end text-sm tabular-nums dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-1.5 text-xs text-mist-500 dark:text-mist-400">
                    أدخل القيمة موجبة دائماً — تُحدد الإشارة تلقائياً من نوع البند.
                </p>
            </div>
        @endif
    @endif

    <div>
        <label for="notes" class="{{ $labelClass }}">ملاحظات</label>
        <textarea id="notes" name="notes" rows="3" class="{{ $inputClass }}">{{ old('notes', $run->notes) }}</textarea>
        @error('notes')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ $isCreate ? route('finance.payroll-runs.index') : route('finance.payroll-runs.show', $run) }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow hover:bg-brand-600">
            {{ $isCreate ? 'إنشاء المسودة' : 'حفظ التعديلات' }}
        </button>
    </div>
</form>
