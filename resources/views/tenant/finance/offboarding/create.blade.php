@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
@endphp

<x-layouts.app title="إعداد تسوية نهاية خدمة">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إعداد تسوية نهاية خدمة</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                تُحتسب المكافأة وبدل الإجازات وراتب الشهر الأخير آلياً من العقد وسجل العمل.
            </p>
        </div>

        <form method="POST" action="{{ route('finance.offboarding.store') }}" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            @csrf

            <div>
                <label for="employee_id" class="{{ $labelClass }}">الموظف</label>
                <select id="employee_id" name="employee_id" required class="{{ $inputClass }}">
                    <option value="">اختر موظفاً</option>
                    @foreach ($employees as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('employee_id') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('employee_id')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="last_working_day" class="{{ $labelClass }}">آخر يوم عمل</label>
                    <input id="last_working_day" type="date" dir="ltr" name="last_working_day" required value="{{ old('last_working_day', now()->toDateString()) }}" class="{{ $inputClass }}">
                    @error('last_working_day')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="reason" class="{{ $labelClass }}">سبب إنهاء الخدمة</label>
                    <select id="reason" name="reason" required class="{{ $inputClass }}">
                        @foreach ($reasons as $reason)
                            <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-mist-500 dark:text-mist-400">الاستقالة تُخفّض استحقاق المكافأة حسب مدة الخدمة.</p>
                    @error('reason')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="loan_deduction" class="{{ $labelClass }}">استقطاع سلف</label>
                    <input id="loan_deduction" type="number" step="0.01" min="0" dir="ltr" name="loan_deduction" value="{{ old('loan_deduction', '0') }}" class="{{ $inputClass }} text-end tabular-nums">
                    <p class="mt-1.5 text-xs text-mist-500 dark:text-mist-400">يُدخل يدوياً — لا يوجد سجل سلف في النظام بعد.</p>
                    @error('loan_deduction')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="other_deduction" class="{{ $labelClass }}">استقطاعات أخرى</label>
                    <input id="other_deduction" type="number" step="0.01" min="0" dir="ltr" name="other_deduction" value="{{ old('other_deduction', '0') }}" class="{{ $inputClass }} text-end tabular-nums">
                    @error('other_deduction')
                        <p class="{{ $errorClass }}">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="{{ $labelClass }}">ملاحظات</label>
                <textarea id="notes" name="notes" rows="3" class="{{ $inputClass }}">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="{{ $errorClass }}">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('finance.offboarding.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
                <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">احتساب التسوية</button>
            </div>
        </form>
    </div>
</x-layouts.app>
