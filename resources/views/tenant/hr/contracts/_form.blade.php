@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="employee_id" class="{{ $labelClass }}">الموظف</label>
        <select id="employee_id" name="employee_id" required class="{{ $inputClass }}">
            <option value="">اختر موظفاً</option>
            @foreach ($employees as $id => $name)
                <option value="{{ $id }}" @selected((string) old('employee_id', $contract->employee_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('employee_id')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="contract_type" class="{{ $labelClass }}">نوع العقد</label>
            <select id="contract_type" name="contract_type" required class="{{ $inputClass }}">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('contract_type', $contract->contract_type?->value ?? $contract->contract_type) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('contract_type')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="status" class="{{ $labelClass }}">الحالة</label>
            <select id="status" name="status" required class="{{ $inputClass }}">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $contract->status?->value ?? $contract->status) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="start_date" class="{{ $labelClass }}">تاريخ البداية</label>
            <input id="start_date" type="date" name="start_date" dir="ltr" required value="{{ old('start_date', optional($contract->start_date)->format('Y-m-d')) }}" class="{{ $inputClass }}">
            @error('start_date')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="end_date" class="{{ $labelClass }}">تاريخ النهاية</label>
            <input id="end_date" type="date" name="end_date" dir="ltr" value="{{ old('end_date', optional($contract->end_date)->format('Y-m-d')) }}" class="{{ $inputClass }}">
            @error('end_date')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="probation_end_date" class="{{ $labelClass }}">نهاية التجربة</label>
            <input id="probation_end_date" type="date" name="probation_end_date" dir="ltr" value="{{ old('probation_end_date', optional($contract->probation_end_date)->format('Y-m-d')) }}" class="{{ $inputClass }}">
            @error('probation_end_date')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="notes" class="{{ $labelClass }}">ملاحظات</label>
        <textarea id="notes" name="notes" rows="3" class="{{ $inputClass }}">{{ old('notes', $contract->notes) }}</textarea>
        @error('notes')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('hr.contracts.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow hover:bg-brand-600">حفظ</button>
    </div>
</form>
