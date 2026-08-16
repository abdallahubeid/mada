@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="title" class="{{ $labelClass }}">البيان</label>
        <input id="title" type="text" name="title" required value="{{ old('title', $expense->title) }}" class="{{ $inputClass }}">
        @error('title')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="amount" class="{{ $labelClass }}">القيمة</label>
            <input id="amount" type="number" step="0.01" min="0.01" dir="ltr" name="amount" required
                   value="{{ old('amount', $expense->amount ? number_format($expense->amount / 100, 2, '.', '') : '') }}"
                   class="{{ $inputClass }} text-end tabular-nums">
            @error('amount')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="expense_date" class="{{ $labelClass }}">التاريخ</label>
            <input id="expense_date" type="date" dir="ltr" name="expense_date" required value="{{ old('expense_date', optional($expense->expense_date)->format('Y-m-d')) }}" class="{{ $inputClass }}">
            @error('expense_date')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="expense_category_id" class="{{ $labelClass }}">التصنيف</label>
            <select id="expense_category_id" name="expense_category_id" class="{{ $inputClass }}">
                <option value="">بدون تصنيف</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $expense->expense_category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('expense_category_id')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="employee_id" class="{{ $labelClass }}">الموظف صاحب المطالبة (اختياري)</label>
        <select id="employee_id" name="employee_id" class="{{ $inputClass }}">
            <option value="">مصروف على المؤسسة</option>
            @foreach ($employees as $id => $name)
                <option value="{{ $id }}" @selected((string) old('employee_id', $expense->employee_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('employee_id')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
        <input type="checkbox" name="is_claimable" value="1" @checked(old('is_claimable', $expense->is_claimable ?? true))>
        قابل للاسترداد (دفعه الموظف من حسابه)
    </label>
    <p class="text-xs text-mist-500 dark:text-mist-400">
        المصروف غير القابل للاسترداد سُدّد مباشرة من المؤسسة، ولا يُصرف لأحد بعد الاعتماد.
    </p>

    <div>
        <label for="description" class="{{ $labelClass }}">تفاصيل</label>
        <textarea id="description" name="description" rows="3" class="{{ $inputClass }}">{{ old('description', $expense->description) }}</textarea>
        @error('description')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="receipt" class="{{ $labelClass }}">إيصال (PDF أو صورة)</label>
        <input id="receipt" type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" class="{{ $inputClass }}">
        @if ($expense->receipt_path)
            <p class="mt-1.5 text-xs text-mist-500 dark:text-mist-400">يوجد إيصال مرفق — الرفع يستبدله.</p>
        @endif
        @error('receipt')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('finance.expenses.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow hover:bg-brand-600">حفظ</button>
    </div>
</form>
