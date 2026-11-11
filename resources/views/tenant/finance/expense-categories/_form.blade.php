@php
    $inputClass = 'ui-control w-full';
    $labelClass = 'ui-label';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
@endphp

<form method="POST" action="{{ $action }}" class="ui-card space-y-4 p-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="{{ $labelClass }}">اسم التصنيف</label>
            <input id="name" type="text" name="name" required value="{{ old('name', $category->name) }}" class="{{ $inputClass }}">
            @error('name')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="code" class="{{ $labelClass }}">الرمز (اختياري)</label>
            <input id="code" type="text" dir="ltr" name="code" value="{{ old('code', $category->code) }}" class="{{ $inputClass }}">
            @error('code')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="sort_order" class="{{ $labelClass }}">الترتيب</label>
            <input id="sort_order" type="number" min="0" dir="ltr" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="{{ $inputClass }} text-end tabular-nums">
            @error('sort_order')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <label class="inline-flex items-end gap-2 pb-3 text-sm text-ink-700 dark:text-mist-200">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
            مفعّل
        </label>
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('finance.expense-categories.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="ui-btn ui-btn-primary">حفظ</button>
    </div>
</form>
