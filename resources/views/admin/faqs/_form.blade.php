@php
    $inputClass = 'ui-control w-full';
    $labelClass = 'ui-label';
@endphp

<form method="POST" action="{{ $action }}" class="ui-card space-y-4 p-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="f--form-category-1" class="{{ $labelClass }}">التصنيف</label>
        <input id="f--form-category-1" type="text" name="category" value="{{ old('category', $faq->category) }}" class="{{ $inputClass }}" required>
    </div>
    <div>
        <label for="f--form-question-2" class="{{ $labelClass }}">السؤال</label>
        <input id="f--form-question-2" type="text" name="question" value="{{ old('question', $faq->question) }}" class="{{ $inputClass }}" required>
    </div>
    <div>
        <label for="f--form-answer-3" class="{{ $labelClass }}">الإجابة</label>
        <textarea id="f--form-answer-3" name="answer" rows="5" class="{{ $inputClass }}" required>{{ old('answer', $faq->answer) }}</textarea>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label for="f--form-sort-order-4" class="{{ $labelClass }}">ترتيب العرض</label>
            <input id="f--form-sort-order-4" type="number" name="sort_order" value="{{ old('sort_order', $faq->sort_order) }}" class="{{ $inputClass }}">
        </div>
        <label class="mt-7 flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $faq->is_published)) class="rounded border-mist-300 text-brand-500 focus:ring-brand-500">
            منشور على الموقع
        </label>
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('admin.faqs.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="ui-btn ui-btn-primary">حفظ</button>
    </div>
</form>
