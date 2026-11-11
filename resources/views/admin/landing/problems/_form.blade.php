@php
    $inputClass = 'ui-control w-full';
    $labelClass = 'ui-label';
    $image = $problem->relationLoaded('images')
        ? $problem->images->firstWhere('collection', 'icon')
        : $problem->image('icon')->first();
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="ui-card space-y-4 p-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="f--form-title-1" class="{{ $labelClass }}">العنوان</label>
        <input id="f--form-title-1" type="text" name="title" value="{{ old('title', $problem->title) }}" class="{{ $inputClass }}" required>
    </div>

    <div>
        <label for="f--form-description-2" class="{{ $labelClass }}">الوصف</label>
        <textarea id="f--form-description-2" name="description" rows="4" class="{{ $inputClass }}" required>{{ old('description', $problem->description) }}</textarea>
    </div>


    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label for="f--form-icon-key-3" class="{{ $labelClass }}">مفتاح الأيقونة</label>
            <input id="f--form-icon-key-3" type="text" dir="ltr" name="icon_key" value="{{ old('icon_key', $problem->icon_key) }}" class="{{ $inputClass }}" placeholder="shield">
        </div>
        <div>
            <label for="f--form-sort-order-4" class="{{ $labelClass }}">ترتيب العرض</label>
            <input id="f--form-sort-order-4" type="number" name="sort_order" value="{{ old('sort_order', $problem->sort_order) }}" class="{{ $inputClass }}">
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $problem->is_published)) class="rounded border-mist-300 text-brand-500 focus:ring-brand-500">
        منشور على الموقع
    </label>

    <div>
        <label for="f-icon" class="{{ $labelClass }}">صورة الأيقونة</label>
        @if ($image)
            <div class="mb-3 flex items-center gap-3">
                <img src="{{ $image->url() }}" alt="{{ $image->alt_text }}" class="h-14 w-14 rounded-xl object-cover ring-1 ring-mist-200 dark:ring-ink-600">
                <p class="text-xs text-mist-500">{{ $image->path }}</p>
            </div>
        @endif
        <input id="f-icon" type="file" name="icon" accept="image/*" class="{{ $inputClass }}">
        <input type="text" name="alt_text" value="{{ old('alt_text', $image?->alt_text) }}" placeholder="نص بديل للصورة" class="{{ $inputClass }} mt-2">
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('admin.problems.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="ui-btn ui-btn-primary">حفظ</button>
    </div>
</form>