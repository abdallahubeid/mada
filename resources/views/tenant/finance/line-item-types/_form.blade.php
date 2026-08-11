@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="{{ $labelClass }}">اسم البند</label>
            <input id="name" type="text" name="name" required value="{{ old('name', $type->name) }}" class="{{ $inputClass }}">
            @error('name')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="code" class="{{ $labelClass }}">الرمز (اختياري)</label>
            <input id="code" type="text" name="code" dir="ltr" value="{{ old('code', $type->code) }}" class="{{ $inputClass }}">
            @error('code')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="kind" class="{{ $labelClass }}">النوع</label>
            <select id="kind" name="kind" required class="{{ $inputClass }}">
                @foreach ($kinds as $kind)
                    <option value="{{ $kind->value }}" @selected(old('kind', $type->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
            @error('kind')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="default_amount" class="{{ $labelClass }}">القيمة الافتراضية</label>
            <input id="default_amount" type="number" step="0.01" min="0" dir="ltr" name="default_amount" required
                   value="{{ old('default_amount', number_format(abs($type->default_amount ?? 0) / 100, 2, '.', '')) }}"
                   class="{{ $inputClass }} text-end tabular-nums">
            <p class="mt-1.5 text-xs text-mist-500 dark:text-mist-400">أدخل القيمة موجبة — تُحدد الإشارة من النوع.</p>
            @error('default_amount')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="sort_order" class="{{ $labelClass }}">الترتيب</label>
            <input id="sort_order" type="number" min="0" dir="ltr" name="sort_order" value="{{ old('sort_order', $type->sort_order ?? 0) }}" class="{{ $inputClass }} text-end tabular-nums">
            @error('sort_order')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap gap-6">
        <label class="inline-flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active ?? true))>
            مفعّل
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-mist-500 dark:text-mist-400">
            <input type="checkbox" name="is_taxable" value="1" @checked(old('is_taxable', $type->is_taxable ?? false))>
            خاضع للضريبة (محجوز للمرحلة الثانية-ب)
        </label>
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('finance.line-item-types.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow hover:bg-emerald-300">حفظ</button>
    </div>
</form>
