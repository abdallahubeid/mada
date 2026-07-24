@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label class="{{ $labelClass }}">التصنيف</label>
        <input type="text" name="category" value="{{ old('category', $faq->category) }}" class="{{ $inputClass }}" required>
    </div>
    <div>
        <label class="{{ $labelClass }}">السؤال</label>
        <input type="text" name="question" value="{{ old('question', $faq->question) }}" class="{{ $inputClass }}" required>
    </div>
    <div>
        <label class="{{ $labelClass }}">الإجابة</label>
        <textarea name="answer" rows="5" class="{{ $inputClass }}" required>{{ old('answer', $faq->answer) }}</textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="{{ $labelClass }}">ترتيب العرض</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $faq->sort_order) }}" class="{{ $inputClass }}">
        </div>
        <label class="mt-7 flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $faq->is_published)) class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400">
            منشور على الموقع
        </label>
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('admin.faqs.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow">حفظ</button>
    </div>
</form>
